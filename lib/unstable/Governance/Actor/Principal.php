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
 * Identity only: who, and where that identity lives.
 *
 * Deliberately carries no roles, groups, display name or quota. Those are
 * lookup data and get resolved from the id when a policy needs them, which
 * keeps this trivially serializable for jobs and audit logs.
 *
 * @psalm-immutable
 */
final class Principal implements JsonSerializable {
	private function __construct(
		public readonly PrincipalType $type,
		/** uid, federated user id, guest id, bot id or system component */
		public readonly string $id,
		/** remote instance for federated, share/room token for guests, null otherwise */
		public readonly ?string $origin = null,
	) {
		if ($id === '') {
			throw new InvalidArgumentException('Principal id must not be empty');
		}

		$originAllowed = match ($type) {
			PrincipalType::Federated => $origin !== null && $origin !== '',
			PrincipalType::Guest => $origin === null || $origin !== '',
			PrincipalType::User, PrincipalType::Bot, PrincipalType::System => $origin === null,
		};
		if (!$originAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid origin for principal of type "%s"',
				$type->value,
			));
		}
	}

	public static function user(string $uid): self {
		return new self(PrincipalType::User, $uid);
	}

	public static function federated(string $userId, string $remote): self {
		return new self(PrincipalType::Federated, $userId, $remote);
	}

	public static function guest(string $guestId, ?string $token = null): self {
		return new self(PrincipalType::Guest, $guestId, $token);
	}

	public static function bot(string $botId): self {
		return new self(PrincipalType::Bot, $botId);
	}

	/**
	 * Identifies a component, e.g. "governance.retention". This is only an
	 * identity: it grants nothing until an Actor is minted for it.
	 */
	public static function system(string $component): self {
		return new self(PrincipalType::System, $component);
	}

	public function is(PrincipalType $type): bool {
		return $this->type === $type;
	}

	public function equals(self $other): bool {
		return $this->type === $other->type
			&& $this->id === $other->id
			&& $this->origin === $other->origin;
	}

	/** Stable key for logs, cache keys and cycle detection, e.g. "federated:bob@cloud.example" */
	public function key(): string {
		return $this->type->value . ':' . $this->id . ($this->origin !== null ? '@' . $this->origin : '');
	}

	/** @return array{type: string, id: string, origin: ?string} */
	public function jsonSerialize(): array {
		return ['type' => $this->type->value, 'id' => $this->id, 'origin' => $this->origin];
	}

	/**
	 * @param array{type: string, id: string, origin?: ?string} $data
	 * @throws InvalidArgumentException
	 */
	public static function fromArray(array $data): self {
		return new self(
			PrincipalType::from($data['type']),
			$data['id'],
			$data['origin'] ?? null,
		);
	}
}
