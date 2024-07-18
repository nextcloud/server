<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IDBConnection;
use OCP\IL10N;

class SchedulingTableSize {
	private IL10N $l10n;
	private IDBConnection $connection;

	public function __construct(
		IL10N $l10n,
		IDBConnection $connection
	) {
		$this->l10n = $l10n;
		$this->connection = $connection;
	}

	public function description(): string {
		return $this->l10n->t('You have more than 50 000 rows in the scheduling objects table. Please run the expensive repair jobs via occ maintenance:repair --include-expensive');
	}

	public function severity(): string {
		return 'warning';
	}

	public function run(): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select($qb->func()->count('id'))
			->from('schedulingobjects');
		$query = $qb->executeQuery();
		$count = $query->fetchOne();
		$query->closeCursor();

		return $count <= 50000;
	}
}
