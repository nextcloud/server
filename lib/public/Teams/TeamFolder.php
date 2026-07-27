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
	 * @return array{id: int, mountPoint: string}
	 * @since 35.0.0
	 */
	#[\Override]
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'mountPoint' => $this->mountPoint,
		];
	}
}
