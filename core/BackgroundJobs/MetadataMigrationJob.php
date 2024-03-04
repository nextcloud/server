<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\BackgroundJobs;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;

// Migrate oc_file_metadata.metadata to oc_file_metadata.value.
// This was previously done in a migration, but it is taking to much time in large instances.
// This job will progressively migrate the data 1 hour per night every night.
// Once done, it will remove itself from the job list.
class MetadataMigrationJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IDBConnection $db,
		private IJobList $jobList,
		private IConfig $config,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);
		$this->setInterval(24 * 3600);
	}

	protected function run(mixed $argument): void {
		$prefix = $this->config->getSystemValueString('dbtableprefix', 'oc_');
		if (!$this->db->createSchema()->getTable($prefix.'file_metadata')->hasColumn('metadata')) {
			return;
		}

		$updateQuery = $this->db->getQueryBuilder();
		$updateQuery->update('file_metadata')
			->set('value', $updateQuery->createParameter('value'))
			->set('metadata', $updateQuery->createParameter('metadata'))
			->where($updateQuery->expr()->eq('id', $updateQuery->createParameter('id')))
			->andWhere($updateQuery->expr()->eq('group_name', $updateQuery->createParameter('group_name')));

		$selectQuery = $this->db->getQueryBuilder();
		$selectQuery->select('id', 'group_name', 'metadata')
			->from('file_metadata')
			->where($selectQuery->expr()->nonEmptyString('metadata'))
			->setMaxResults(1000);

		$movedRows = 0;
		$startTime = time();

		do {
			// Stop if execution time is more than one hour.
			if (time() - $startTime > 3600) {
				return;
			}
			$movedRows = $this->chunkedCopying($updateQuery, $selectQuery);
		} while ($movedRows !== 0);


		$this->jobList->remove(MetadataMigrationJob::class);
	}

	protected function chunkedCopying(IQueryBuilder $updateQuery, IQueryBuilder $selectQuery): int {
		$this->db->beginTransaction();

		$results = $selectQuery->executeQuery();

		while ($row = $results->fetch()) {
			$updateQuery
				->setParameter('id', (int)$row['id'])
				->setParameter('group_name', $row['group_name'])
				->setParameter('value', $row['metadata'])
				->setParameter('metadata', '')
				->executeStatement();
		}

		$results->closeCursor();
		$this->db->commit();

		return $results->rowCount();
	}
}
