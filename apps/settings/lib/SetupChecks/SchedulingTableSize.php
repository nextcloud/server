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

		if ($count > 500000) {
			return SetupResult::warning(
				$this->l10n->t('You have more than 500 000 rows in the scheduling objects table. Please run the expensive repair jobs via occ maintenance:repair --include-expensive')
			);
		}
		return SetupResult::success(
			$this->l10n->t('Scheduling objects table size is within acceptable range.')
		);
	}
}
