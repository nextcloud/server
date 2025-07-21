<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DeduplicateMounts implements IRepairStep {
	public function __construct(
		private readonly IDBConnection $connection,
		private readonly IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Deduplicate mounts';
	}

	public function run(IOutput $output): void {
		$threshold = $this->config->getSystemValueInt('repair_duplicate_mounts_threshold', 10);
		if ($threshold < 1) {
			$threshold = 1;
		}

		$this->connection->beginTransaction();

		$selectQuery = $this->connection->getQueryBuilder();
		$selectQuery
			->select('root_id', 'user_id', 'mount_point')
			->selectAlias($selectQuery->func()->min('id'), 'min_id')
			->from('mounts')
			->groupBy('root_id', 'user_id', 'mount_point')
			->having($selectQuery->expr()->gt($selectQuery->func()->count('*'), $selectQuery->createNamedParameter($threshold, IQueryBuilder::PARAM_INT)));

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery
			->delete('mounts')
			->where(
				$deleteQuery->expr()->neq('id', $deleteQuery->createParameter('id')),
				$deleteQuery->expr()->eq('root_id', $deleteQuery->createParameter('root_id')),
				$deleteQuery->expr()->eq('user_id', $deleteQuery->createParameter('user_id')),
				$deleteQuery->expr()->eq('mount_point', $deleteQuery->createParameter('mount_point')),
			);

		$result = $selectQuery->executeQuery();
		while ($row = $result->fetch()) {
			$deleteQuery
				->setParameter('id', $row['min_id'])
				->setParameter('root_id', $row['root_id'])
				->setParameter('user_id', $row['user_id'])
				->setParameter('mount_point', $row['mount_point'])
				->executeStatement();
		}
		$result->closeCursor();

		$this->connection->commit();
	}
}
