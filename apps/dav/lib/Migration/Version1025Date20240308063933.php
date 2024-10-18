<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1025Date20240308063933 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
		private IDBConnection $db,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		foreach (['addressbookchanges', 'calendarchanges'] as $tableName) {
			$table = $schema->getTable($tableName);
			if (!$table->hasColumn('created_at')) {
				$table->addColumn('created_at', Types::INTEGER, [
					'notnull' => true,
					'length' => 4,
					'default' => 0,
				]);
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// The threshold is higher than the default of \OCA\DAV\BackgroundJob\PruneOutdatedSyncTokensJob
		// but small enough to fit into a cluster transaction size.
		// For a 50k users instance that would still keep 10 changes on average.
		$limit = max(1, (int)$this->appConfig->getAppValue('totalNumberOfSyncTokensToKeep', '500000'));

		foreach (['addressbookchanges', 'calendarchanges'] as $tableName) {
			$thresholdSelect = $this->db->getQueryBuilder();
			$thresholdSelect->select('id')
				->from($tableName)
				->orderBy('id', 'desc')
				->setFirstResult($limit)
				->setMaxResults(1);
			$oldestIdResult = $thresholdSelect->executeQuery();
			$oldestId = $oldestIdResult->fetchColumn();
			$oldestIdResult->closeCursor();

			$qb = $this->db->getQueryBuilder();

			$update = $qb->update($tableName)
				->set('created_at', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
				->where(
					$qb->expr()->eq('created_at', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
				);

			// If there is a lot of data we only set timestamp for the most recent rows
			// because the rest will be deleted by \OCA\DAV\BackgroundJob\PruneOutdatedSyncTokensJob
			// anyway.
			if ($oldestId !== false) {
				$update->andWhere($qb->expr()->gt('id', $qb->createNamedParameter($oldestId, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
			}

			$updated = $update->executeStatement();

			$output->debug('Added a default creation timestamp to ' . $updated . ' rows in ' . $tableName);
		}
	}

}
