<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IDBConnection;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class SchedulingTableSize implements ISetupCheck {
	public const MAX_SCHEDULING_ENTRIES = 50000;

	public function __construct(
		private IL10N $l10n,
		private IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Scheduling objects table size');
	}

	public function getCategory(): string {
		return 'database';
	}

	public function run(): SetupResult {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('id'))
			->from('schedulingobjects');
		$query = $qb->executeQuery();
		$count = $query->fetchOne();
		$query->closeCursor();

		if ($count > self::MAX_SCHEDULING_ENTRIES) {
			return SetupResult::warning(
				$this->l10n->t('You have more than %s rows in the scheduling objects table. Please run the expensive repair jobs via occ maintenance:repair --include-expensive.', [
					self::MAX_SCHEDULING_ENTRIES,
				])
			);
		}
		return SetupResult::success(
			$this->l10n->t('Scheduling objects table size is within acceptable range.')
		);
	}
}
