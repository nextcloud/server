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
 * Identity only: who, and where that identity lives.
 *
 * Deliberately carries no roles, groups, display name or quota. Those are
 * lookup data and get resolved from the id when a policy needs them, which
 * keeps this trivially serializable for jobs and audit logs.
 *
 * @psalm-immutable
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Principal implements JsonSerializable {
	private function __construct(
		public PrincipalType $type,
		/** uid, federated user id, guest id, bot id or system component */
		public string $id,
		/** remote instance for federated, share/room token for guests, null otherwise */
		public ?string $origin = null,
		/** app-namespaced kind of guest, e.g. "spreed.guest" or "files_sharing.link"; null for non-guests */
		public ?string $source = null,
	) {
		if ($id === '') {
			throw new InvalidArgumentException('Principal id must not be empty');
		}

		$originAllowed = match ($type) {
			PrincipalType::Federated => $origin !== null && $origin !== '',
			PrincipalType::Guest => $origin === null || $origin !== '',
			PrincipalType::User, PrincipalType::Bot, PrincipalType::System => $origin === null,
			default => false,
		};
		if (!$originAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid origin for principal of type "%s"',
				$type->value,
			));
		}

		$sourceAllowed = $type === PrincipalType::Guest
			? $source !== null && $source !== ''
			: $source === null;
		if (!$sourceAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid source for principal of type "%s"',
				$type->value,
			));
		}
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function user(string $uid): self {
		return new self(PrincipalType::User, $uid);
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function federated(string $userId, string $remote): self {
		return new self(PrincipalType::Federated, $userId, $remote);
	}

	/**
	 * @param string $source app-namespaced kind of guest, e.g. "spreed.email"
	 * @experimental 36.0.0
	 */
	public static function guest(string $source, string $guestId, ?string $token = null): self {
		return new self(PrincipalType::Guest, $guestId, $token, $source);
	}

	/**
	 * @experimental 36.0.0
	 */
	public static function bot(string $botId): self {
		return new self(PrincipalType::Bot, $botId);
	}

	/**
	 * Identifies a component, e.g. "governance.retention".
	 *
	 * @experimental 36.0.0
	 */
	public static function system(string $component): self {
		return new self(PrincipalType::System, $component);
	}

	/**
	 * @experimental 36.0.0
	 */
	public function is(PrincipalType $type): bool {
		return $this->type === $type;
	}

	/**
	 * @experimental 36.0.0
	 */
	public function equals(self $other): bool {
		return $this->type === $other->type
			&& $this->id === $other->id
			&& $this->origin === $other->origin
			&& $this->source === $other->source;
	}

	/**
	 * Stable key for logs, cache keys and cycle detection, e.g. "federated:bob@cloud.example"
	 * or "guest:spreed.guest:abc123@roomtoken"
	 *
	 * @experimental 36.0.0
	 */
	public function key(): string {
		return $this->type->value . ':'
			. ($this->source !== null ? $this->source . ':' : '')
			. $this->id
			. ($this->origin !== null ? '@' . $this->origin : '');
	}

	/**
	 * @return array{type: string, id: string, origin: ?string, source: ?string}
	 * @experimental 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return ['type' => $this->type->value, 'id' => $this->id, 'origin' => $this->origin, 'source' => $this->source];
	}

	/**
	 * @param array<array-key, mixed> $data
	 * @throws InvalidArgumentException
	 * @experimental 36.0.0
	 */
	public static function fromArray(array $data): self {
		$type = $data['type'] ?? null;
		$id = $data['id'] ?? null;
		$origin = $data['origin'] ?? null;
		$source = $data['source'] ?? null;
		if (!is_string($type) || !is_string($id)
			|| ($origin !== null && !is_string($origin))
			|| ($source !== null && !is_string($source))) {
			throw new InvalidArgumentException('Invalid principal data');
		}

		return new self(
			PrincipalType::tryFrom($type) ?? throw new InvalidArgumentException(sprintf('Unknown principal type "%s"', $type)),
			$id,
			$origin,
			$source,
		);
	}
}
