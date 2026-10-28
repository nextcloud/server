<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Actor;

use InvalidArgumentException;
use JsonSerializable;
use OCP\AppFramework\Attribute\Consumable;
use Override;

/**
 * Transport information about whose authority is being claimed to perform an
 * operation ($principal) and, if someone else is exercising it, who that is
 * ($delegate), recursively.
 *
 * Creating an Actor asserts nothing. Callers check whether the operation is
 * permitted before creating it. Consumers receiving an Actor from another app,
 * a job payload or a request, check the principal's permissions themselves.
 *
 * Examples:
 *   bot "summarizer" for alice:      principal=alice,  delegate=Actor(bot),   via=OnBehalfOf
 *   version expiry in alice's files: principal=alice,  delegate=Actor(system), via=OnBehalfOf
 *   admin impersonating alice:       principal=alice,  delegate=Actor(admin), via=Impersonation
 *   alice's action escalated:        principal=system, delegate=Actor(alice), via=SystemElevation
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Actor implements JsonSerializable {
	/**
	 * @experimental 36.0.0
	 */
	public const int MAX_CHAIN_LENGTH = 16;

	private function __construct(
		public Principal $principal,
		public ?self $delegate = null,
		public ?DelegationKind $via = null,
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
	 * Actor with no delegation.
	 *
	 * @experimental 36.0.0
	 */
	public static function of(Principal $principal): self {
		return new self($principal);
	}

	/**
	 * $this acts as $target with the target's full view.
	 *
	 * @experimental 36.0.0
	 */
	public function impersonate(Principal $target): self {
		return new self($target, $this, DelegationKind::Impersonation);
	}

	/**
	 * $this acts on behalf of $subject.
	 *
	 * @experimental 36.0.0
	 */
	public function actFor(Principal $subject): self {
		return new self($subject, $this, DelegationKind::OnBehalfOf);
	}

	/**
	 * Continue claiming the authority of a system component.
	 *
	 * @experimental 36.0.0
	 */
	public function elevate(Principal $system): self {
		return new self($system, $this, DelegationKind::SystemElevation);
	}

	/**
	 * @experimental 36.0.0
	 */
	public function isDelegated(): bool {
		return $this->delegate !== null;
	}

	/**
	 * The party at the end of the chain that actually started this (bot, admin, user who triggered elevation).
	 *
	 * @experimental 36.0.0
	 */
	public function initiator(): Principal {
		return $this->delegate?->initiator() ?? $this->principal;
	}

	/**
	 * @return non-empty-list<Principal> principal first, initiator last
	 * @experimental 36.0.0
	 */
	public function chain(): array {
		return [$this->principal, ...($this->delegate?->chain() ?? [])];
	}

	/**
	 * Whether any link in the chain is of the given type, e.g. "is a bot involved at all?"
	 *
	 * @experimental 36.0.0
	 */
	public function involves(PrincipalType $type): bool {
		foreach ($this->chain() as $p) {
			if ($p->is($type)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'principal' => $this->principal,
			'via' => $this->via?->value,
			'delegate' => $this->delegate,
		];
	}

	/**
	 * Rehydrate from a serialized  payload.
	 *
	 * @param array<array-key, mixed> $data
	 * @throws InvalidArgumentException
	 * @experimental 36.0.0
	 */
	public static function fromArray(array $data): self {
		$principal = $data['principal'] ?? null;
		$delegate = $data['delegate'] ?? null;
		$via = $data['via'] ?? null;
		if (!is_array($principal) || ($delegate !== null && !is_array($delegate)) || ($via !== null && !is_string($via))) {
			throw new InvalidArgumentException('Invalid actor data');
		}

		return new self(Principal::fromArray($principal),
			$delegate !== null ? self::fromArray($delegate) : null,
			$via !== null ? DelegationKind::tryFrom($via) ?? throw new InvalidArgumentException(sprintf('Unknown delegation kind "%s"', $via))
				: null, );
	}

	private static function assertValidLink(Principal $principal, Actor $delegate, DelegationKind $via): void {
		$fail = static fn (string $why): never => throw new InvalidArgumentException(
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
				!$delegate->principal->is(PrincipalType::Bot) && !$delegate->principal->is(PrincipalType::System) => $fail('only bots and system components act on behalf of others'),
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
