<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Repair;

use OCP\Constants;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Repairs shares with invalid data
 */
class RepairInvalidShares implements IRepairStep {
	public const CHUNK_SIZE = 200;

	public function __construct(
		protected IConfig $config,
		protected IDBConnection $connection,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Repair invalid shares';
	}

	/**
	 * Adjust file share permissions
	 */
	private function adjustFileSharePermissions(IOutput $output): void {
		$mask = Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE;
		$builder = $this->connection->getQueryBuilder();

		$permsFunc = $builder->expr()->bitwiseAnd('permissions', $mask);
		$builder
			->update('share')
			->set('permissions', $permsFunc)
			->where($builder->expr()->eq('item_type', $builder->expr()->literal('file')))
			->andWhere($builder->expr()->neq('permissions', $permsFunc));

		$updatedEntries = $builder->executeStatement();
		if ($updatedEntries > 0) {
			$output->info('Fixed file share permissions for ' . $updatedEntries . ' shares');
		}
	}

	/**
	 * Remove shares where the parent share does not exist anymore
	 */
	private function removeSharesNonExistingParent(IOutput $output): void {
		$deletedEntries = 0;

		$query = $this->connection->getQueryBuilder();
		$query->select('s1.parent')
			->from('share', 's1')
			->where($query->expr()->isNotNull('s1.parent'))
			->andWhere($query->expr()->isNull('s2.id'))
			->leftJoin('s1', 'share', 's2', $query->expr()->eq('s1.parent', 's2.id'))
			->groupBy('s1.parent')
			->setMaxResults(self::CHUNK_SIZE);

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('share')
			->where($deleteQuery->expr()->in('parent', $deleteQuery->createParameter('parent')));

		while (true) {
			$result = $query->executeQuery();
			$parents = $result->fetchFirstColumn();
			$parents = array_unique($parents);
			$result->closeCursor();

			if ($parents === []) {
				break;
			}

			$deletedEntriesInIteration = $deleteQuery->setParameter('parent', $parents, IQueryBuilder::PARAM_INT_ARRAY)
				->executeStatement();
			$deletedEntries += $deletedEntriesInIteration;

			if ($deletedEntriesInIteration === 0) {
				break;
			}
		}

		if ($deletedEntries) {
			$output->info('Removed ' . $deletedEntries . ' shares where the parent did not exist');
		}
	}

	#[\Override]
	public function run(IOutput $output) {
		$ocVersionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0');
		if (version_compare($ocVersionFromBeforeUpdate, '12.0.0.11', '<')) {
			$this->adjustFileSharePermissions($output);
		}

		$this->removeSharesNonExistingParent($output);
	}
}
