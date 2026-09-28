<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Security\Bruteforce;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class CleanupJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IDBConnection $connection,
	) {
		parent::__construct($time);

		// Run once a day
		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument): void {
		// Delete all entries more than 48 hours old
		$time = $this->time->getTime() - (48 * 3600);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('bruteforce_attempts')
			->where($qb->expr()->lt('occurred', $qb->createNamedParameter($time), IQueryBuilder::PARAM_INT));
		$qb->executeStatement();
	}
}
