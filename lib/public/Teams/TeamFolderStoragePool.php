<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Allocated storage is the sum of folder quotas, not the used file space.
 * Used storage is the sum of cached folder-root sizes; null denotes unknown usage.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class TeamFolderStoragePool implements \JsonSerializable {
	/** @since 36.0.0 */
	public function __construct(
		public readonly string $id,
		public readonly ?string $groupId,
		public readonly ?int $limit,
		public readonly int $allocated,
		public readonly bool $hasUnlimitedFolders,
		public readonly ?string $teamId = null,
		public readonly ?int $used = null,
	) {
	}

	/**
	 * @return array{id: string, groupId: ?string, teamId: ?string, limit: ?int, allocated: int, hasUnlimitedFolders: bool, used: ?int}
	 * @since 36.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'groupId' => $this->groupId,
			'teamId' => $this->teamId,
			'limit' => $this->limit,
			'allocated' => $this->allocated,
			'hasUnlimitedFolders' => $this->hasUnlimitedFolders,
			'used' => $this->used,
		];
	}
}