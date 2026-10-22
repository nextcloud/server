<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Actor;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Whose authority is used ($principal) and, if someone else is exercising it,
 * who that is ($delegate), recursively.
 *
 *   bot "summarizer" for alice:      principal=alice,  delegate=Actor(bot),   via=OnBehalfOf
 *   admin impersonating alice:       principal=alice,  delegate=Actor(admin), via=Impersonation
 *   alice's action escalated:        principal=system, delegate=Actor(alice), via=SystemElevation
 *
 * Every link is validated on construction, so a wrongly nested chain cannot
 * exist. Minting is @internal: only entry points and the delegation layer
 * create actors, everything else receives one. Chains only grow from an
 * existing actor (impersonate/actFor/elevate on $this), so callers can add a
 * link on top of who they are but never forge one underneath.
 *
 * What a delegated actor may do is decided by policy, which sees the whole
 * chain (see initiator() and involves()).
 *
 * @psalm-immutable
 */
final class Actor implements JsonSerializable {
	public const MAX_CHAIN_LENGTH = 4;

	private function __construct(
		public readonly Principal $principal,
		public readonly ?Actor $delegate = null,
		public readonly ?DelegationKind $via = null,
	) {
		if (($delegate === null) !== ($via === null)) {
			throw new InvalidArgumentException('A delegate requires a delegation kind and vice versa');
		}
		if ($delegate !== null && $via !== null) {
			self::assertValidLink($principal, $delegate, $via);
		}

		$chain = $this->chain();
		if (count($chain) > self::MAX_CHAIN_LENGTH) {
			throw new InvalidArgumentException('Delegation chain too long');
		}
		$keys = array_map(static fn (Principal $p) => $p->key(), $chain);
		if (count($keys) !== count(array_unique($keys))) {
			throw new InvalidArgumentException('Principal appears more than once in delegation chain');
		}
	}

	/**
	 * Root actor for an authenticated or otherwise established identity.
	 *
	 * @internal Entry points only (request bootstrap, job runner, federation, bot handlers).
	 */
	public static function of(Principal $principal): self {
		return new self($principal);
	}

	/**
	 * $this (an admin) acts as $target with the target's full view.
	 * Whether $this may impersonate is a policy decision made before calling.
	 *
	 * @internal Delegation layer only.
	 */
	public function impersonate(Principal $target): self {
		return new self($target, $this, DelegationKind::Impersonation);
	}

	/**
	 * $this (a bot) acts for $subject. The caller must have verified that the
	 * bot is enabled for that subject (e.g. installed in the conversation).
	 *
	 * @internal Delegation layer only.
	 */
	public function actFor(Principal $subject): self {
		return new self($subject, $this, DelegationKind::OnBehalfOf);
	}

	/**
	 * Continue with the authority of a system component; $this stays recorded as trigger.
	 *
	 * @internal Delegation layer only.
	 */
	public function elevate(Principal $system): self {
		return new self($system, $this, DelegationKind::SystemElevation);
	}

	public function isDelegated(): bool {
		return $this->delegate !== null;
	}

	/** The party at the end of the chain that actually started this (bot, admin, user who triggered elevation). */
	public function initiator(): Principal {
		return $this->delegate?->initiator() ?? $this->principal;
	}

	/**
	 * @return non-empty-list<Principal> principal first, initiator last
	 */
	public function chain(): array {
		return [$this->principal, ...($this->delegate?->chain() ?? [])];
	}

	/** Whether any link in the chain is of the given type, e.g. "is a bot involved at all?" */
	public function involves(PrincipalType $type): bool {
		foreach ($this->chain() as $p) {
			if ($p->is($type)) {
				return true;
			}
		}
		return false;
	}

	public function jsonSerialize(): array {
		return [
			'principal' => $this->principal,
			'via' => $this->via?->value,
			'delegate' => $this->delegate,
		];
	}

	/**
	 * Rehydrate from a job payload. Runs through the constructor, so all
	 * invariants are checked again.
	 *
	 * @throws InvalidArgumentException
	 * @internal Job runner only.
	 */
	public static function fromArray(array $data): self {
		return new self(
			Principal::fromArray($data['principal']),
			isset($data['delegate']) ? self::fromArray($data['delegate']) : null,
			isset($data['via']) ? DelegationKind::from($data['via']) : null,
		);
	}

	private static function assertValidLink(Principal $principal, Actor $delegate, DelegationKind $via): void {
		$fail = static fn (string $why) => throw new InvalidArgumentException(
			sprintf('Invalid %s: %s', $via->value, $why),
		);

		match ($via) {
			DelegationKind::Impersonation => match (true) {
				!$principal->is(PrincipalType::User) => $fail('only local users can be impersonated'),
				!$delegate->principal->is(PrincipalType::User) => $fail('only local users can impersonate'),
				$delegate->isDelegated() => $fail('nested impersonation is not allowed'),
				default => null,
			},
			DelegationKind::OnBehalfOf => match (true) {
				!$principal->type->isHuman() => $fail('subject must be a human principal'),
				!$delegate->principal->is(PrincipalType::Bot) => $fail('only bots act on behalf of others'),
				default => null,
			},
			DelegationKind::SystemElevation => match (true) {
				!$principal->is(PrincipalType::System) => $fail('target must be a system principal'),
				$delegate->principal->is(PrincipalType::System) => $fail('system cannot elevate to system'),
				default => null,
			},
		};
	}
}
