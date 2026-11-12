<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UserStatus\Service;

use OCA\UserStatus\Db\UserStatusMapper;

/**
 * Single place deciding what "stranded" and "orphaned" mean.
 */
class StatusRepairService {

	public function __construct(
		private UserStatusMapper $mapper,
	) {
	}

	/**
	 * @return list<int>
	 */
	public function findStrandedBackupIds(): array {
		return $this->mapper->findStrandedBackupIds(StatusService::AUTOMATED_MESSAGE_IDS);
	}

	/**
	 * @return int Number of deleted backup rows
	 */
	public function deleteStrandedBackups(): int {
		return $this->mapper->deleteStrandedBackups(StatusService::AUTOMATED_MESSAGE_IDS);
	}

	/**
	 * @return list<int>
	 */
	public function findOrphanedAutomatedStatusIds(): array {
		return $this->mapper->findOrphanedAutomatedStatusIds(StatusService::AUTOMATED_MESSAGE_IDS);
	}

	/**
	 * @return list<int>
	 */
	public function findStatusesWithoutBackupFlagIds(): array {
		return $this->mapper->findStatusesWithoutBackupFlagIds();
	}

	/**
	 * @param list<int> $ids
	 * @return int Number of rows given an explicit is_backup value
	 */
	public function normalizeBackupFlagByIds(array $ids): int {
		return $this->mapper->normalizeBackupFlagByIds($ids);
	}

	/**
	 * @param list<int> $ids
	 * @return int Number of deleted rows
	 */
	public function deleteByIds(array $ids): int {
		return $this->mapper->deleteByIds($ids);
	}
}
