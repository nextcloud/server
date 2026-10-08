<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Teams;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A folder exclusively linked to a team.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
class TeamFolder implements \JsonSerializable {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		private int $id,
		private string $mountPoint,
		private ?int $quota = null,
		private ?int $rootFileId = null,
	) {
	}

	/**
	 * @since 35.0.0
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @since 35.0.0
	 */
	public function getMountPoint(): string {
		return $this->mountPoint;
	}

	/**
	 * The storage quota in bytes, zero for unlimited, or null if the active
	 * provider does not expose a quota for this folder.
	 *
	 * @since 35.0.0
	 */
	public function getQuota(): ?int {
		return $this->quota;
	}

	/**
	 * Return the filecache ID of the folder's root, when exposed by the provider.
	 *
	 * @since 36.0.0
	 */
	public function getRootFileId(): ?int {
		return $this->rootFileId;
	}

	/**
	 * @return array{id: int, quota: int|null, mountPoint: string, rootFileId: int|null}
	 * @since 35.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'quota' => $this->quota,
			'mountPoint' => $this->mountPoint,
			'rootFileId' => $this->rootFileId,
		];
	}
}
