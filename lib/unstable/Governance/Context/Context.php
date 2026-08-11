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
use OCP\IUserSession;
use RuntimeException;

/**
 * Who is asking Governance about labels, and why. Required on every
 * ILabelManager call; there is deliberately no implicit default.
 *
 * This only describes the request. Whether it is allowed is decided by the
 * label policy over (actor, purpose, node, old label, new label).
 *
 * @psalm-immutable
 */
final class Context implements JsonSerializable {
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

	public static function forUser(string $uid, Purpose $purpose): self {
		return new self(Actor::of(Principal::user($uid)), $purpose);
	}

	public static function forFederatedUser(string $userId, string $remote, Purpose $purpose): self {
		return new self(Actor::of(Principal::federated($userId, $remote)), $purpose);
	}

	public static function forGuest(string $guestId, ?string $token, Purpose $purpose): self {
		return new self(Actor::of(Principal::guest($guestId, $token)), $purpose);
	}

	/**
	 * @param string $component e.g. "governance.retention", "governance.autolabel"
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
	 */
	public static function forBot(string $botId, string $onBehalfOfUid, Purpose $purpose): self {
		$actor = Actor::of(Principal::bot($botId))
			->actFor(Principal::user($onBehalfOfUid));
		return new self($actor, $purpose);
	}

	/**
	 * Convenience for plain UI/API requests. Throws instead of silently
	 * falling back to some other identity when there is no logged-in user.
	 *
	 * @throws RuntimeException
	 */
	public static function fromSession(IUserSession $session, Purpose $purpose): self {
		$user = $session->getUser();
		if ($user === null) {
			throw new RuntimeException('No user in session; build the LabelRequest explicitly');
		}
		return self::forUser($user->getUID(), $purpose);
	}

	/** For actors coming from the delegation layer or a rehydrated job payload. */
	public static function fromActor(Actor $actor, Purpose $purpose): self {
		return new self($actor, $purpose);
	}

	public function withJustification(string $justification): self {
		return new self($this->actor, $this->purpose, $justification);
	}

	public function hasJustification(): bool {
		return $this->justification !== null;
	}

	/** For the audit log: full chain, purpose and justification. */
	public function jsonSerialize(): array {
		return [
			'actor' => $this->actor,
			'purpose' => $this->purpose->value,
			'justification' => $this->justification,
		];
	}
}
