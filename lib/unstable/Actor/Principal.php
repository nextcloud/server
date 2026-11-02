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
 * @psalm-import-type PrincipalData from DataTypes
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final readonly class Principal implements JsonSerializable {
	/**
	 * @param non-empty-string $id - uid, federated user id, external user id, bot id or system component
	 * @param ?non-empty-string $origin - remote instance for federated users, share/room token for external users, null otherwise
	 * @param ?non-empty-string $source - app-namespaced kind of external user, e.g. "spreed.guest" or "files_sharing.link"; null for others
	 */
	private function __construct(
		public PrincipalType $type,
		public string $id,
		public ?string $origin = null,
		public ?string $source = null,
	) {
		$originAllowed = match ($type) {
			PrincipalType::FederatedUser => $origin !== null && $origin !== '',
			PrincipalType::External => $origin === null || $origin !== '',
			PrincipalType::User, PrincipalType::Bot, PrincipalType::System => $origin === null,
			default => false,
		};
		if (!$originAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid origin for principal of type "%s"',
				$type->value,
			));
		}

		$sourceAllowed = $type === PrincipalType::External
			? $source !== null
			: $source === null;
		if (!$sourceAllowed) {
			throw new InvalidArgumentException(sprintf(
				'Invalid source for principal of type "%s"',
				$type->value,
			));
		}
	}

	/**
	 * @param non-empty-string $uid - user id
	 * @experimental 36.0.0
	 */
	public static function user(string $uid): self {
		return new self(PrincipalType::User, $uid);
	}

	/**
	 * @param non-empty-string $userId - federated user id
	 * @param non-empty-string $remote - remote instance
	 * @experimental 36.0.0
	 */
	public static function federated(string $userId, string $remote): self {
		return new self(PrincipalType::FederatedUser, $userId, $remote);
	}

	/**
	 * @param non-empty-string $source app-namespaced kind of external user, e.g. "spreed.email"
	 * @param non-empty-string $externalId - external user id
	 * @param ?non-empty-string $token - share/room token, if any
	 * @experimental 36.0.0
	 */
	public static function external(string $source, string $externalId, ?string $token = null): self {
		return new self(PrincipalType::External, $externalId, $token, $source);
	}

	/**
	 * @param non-empty-string $botId - bot id
	 * @experimental 36.0.0
	 */
	public static function bot(string $botId): self {
		return new self(PrincipalType::Bot, $botId);
	}

	/**
	 * Identifies a component, e.g. "governance.retention".
	 *
	 * @param non-empty-string $component - component name
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
	 * Stable key for logs, cache keys and cycle detection, e.g. "federated_user:bob@cloud.example"
	 * or "external:abc123@roomtoken:spreed.guest"
	 *
	 * @experimental 36.0.0
	 */
	public function key(): string {
		return $this->type->value . ':'
			. $this->id
			. ($this->origin !== null ? '@' . $this->origin : '')
			. ($this->source !== null ? ':' . $this->source : '');
	}

	/**
	 * @return PrincipalData
	 * @experimental 36.0.0
	 */
	#[Override]
	public function jsonSerialize(): array {
		return ['type' => $this->type->value, 'id' => $this->id, 'origin' => $this->origin, 'source' => $this->source];
	}

	/**
	 * @param array<string, mixed> $data - see PrincipalData
	 * @throws InvalidArgumentException if the data is invalid or the type is unknown
	 * @experimental 36.0.0
	 */
	public static function fromArray(array $data): self {
		self::assertValidData($data);
		$type = $data['type'];
		$id = $data['id'];
		$origin = $data['origin'] ?? null;
		$source = $data['source'] ?? null;

		return new self(
			PrincipalType::tryFrom($type) ?? throw new InvalidArgumentException(sprintf('Unknown principal type "%s"', $type)),
			$id,
			$origin,
			$source,
		);
	}

	/**
	 * @psalm-assert array{type: non-empty-string, id: non-empty-string, origin?: ?non-empty-string, source?: ?non-empty-string} $data
	 * @throws InvalidArgumentException - if the data is invalid
	 */
	private static function assertValidData(array $data): void {
		$type = $data['type'] ?? null;
		$id = $data['id'] ?? null;
		$origin = $data['origin'] ?? null;
		$source = $data['source'] ?? null;
		if (!is_string($type) || $type === ''
			|| !is_string($id) || $id === ''
			|| ($origin !== null && !is_string($origin)) || $origin === ''
			|| ($source !== null && !is_string($source)) || $source === '') {
			throw new InvalidArgumentException('Invalid principal data');
		}
	}
}
