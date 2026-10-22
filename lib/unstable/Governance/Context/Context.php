<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Context;

use InvalidArgumentException;
use JsonSerializable;
use NCU\Governance\Actor\Actor;
use NCU\Governance\Actor\Principal;
use OCP\AppFramework\Attribute\Consumable;

/**
 * Who is asking Governance about labels, and why.
 *
 * This only describes the request. Whether it is allowed is decided by the
 * label policy over (actor, purpose, resource, old label, new label).
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class Context implements JsonSerializable {
	/**
	 * @experimental 36.0.0
	 */
	public const MAX_JUSTIFICATION_LENGTH = 1000;

	private function __construct(
		public readonly Actor $actor,
		public readonly Purpose $purpose,
		public readonly ?string $justification = null,
	) {
		if ($justification !== null) {
			if (trim($justification) === '') {
				throw new InvalidArgumentException('Justification must not be blank');
			}
			if (mb_strlen($justification) > self::MAX_JUSTIFICATION_LENGTH) {
				throw new InvalidArgumentException('Justification too long');
			}
		}
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function forUser(string $id, Purpose $purpose): self {
		return new self(Actor::of(Principal::user($id)), $purpose);
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function forFederatedUser(string $id, string $remote, Purpose $purpose): self {
		return new self(Actor::of(Principal::federated($id, $remote)), $purpose);
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function forGuest(string $id, ?string $token, Purpose $purpose): self {
		return new self(Actor::of(Principal::guest($id, $token)), $purpose);
	}

	/**
	 * @param string $component e.g. "governance.retention", "governance.autolabel"
	 * @experimental 36.0.0
	 */
	public static function forSystem(string $component, Purpose $purpose): self {
		return new self(Actor::of(Principal::system($component)), $purpose);
	}

	/**
	 * Bot acting for a local user. Which purposes bots may use is up to the
	 * label policy (e.g. only Display and Enforcement).
	 *
	 * The caller must already have verified that the bot is enabled for this
	 * user/conversation (e.g. Talk when dispatching to the bot).
	 *
	 * @experimental 36.0.0
	 */
	public static function forBot(string $id, string $onBehalfOfUid, Purpose $purpose): self {
		$actor = Actor::of(Principal::bot($id))
			->actFor(Principal::user($onBehalfOfUid));
		return new self($actor, $purpose);
	}

	/**
	 * For actors coming from the delegation layer or a rehydrated job payload.
	 *
	 * @experimental 36.0.0
	 */
	public static function fromActor(Actor $actor, Purpose $purpose): self {
		return new self($actor, $purpose);
	}

	/**
	 * @experimental 36.0.0
	 */
	public function withJustification(string $justification): self {
		return new self($this->actor, $this->purpose, $justification);
	}

	/**
	 * @experimental 36.0.0
	 */
	public function hasJustification(): bool {
		return $this->justification !== null;
	}

	/**
	 * For the audit log: full chain, purpose and justification.
	 *
	 * @experimental 36.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'actor' => $this->actor,
			'purpose' => $this->purpose->value,
			'justification' => $this->justification,
		];
	}
}
