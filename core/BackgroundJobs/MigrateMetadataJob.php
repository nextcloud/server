<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Louis Chemineau <louis@chmn.me>
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
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IConfig;
use OCP\IDBConnection;

class MigrateMetadataJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IFilesMetadataManager $filesMetadataManager,
		private IDBConnection $connection,
		private IJobList $jobList,
	) {
		parent::__construct($time);

		$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);
		$this->setInterval(24 * 3600);
	}

	protected function run(mixed $argument): void {
		if (!$this->connection->tableExists('file_metadata')) {
			return;
		}

		$startTime = time();

		$selectQuery = $this->connection->getQueryBuilder()
			->select('*')
			->from('file_metadata')
			->setMaxResults(200);

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('file_metadata')
			->where($deleteQuery->expr()->eq('id', $deleteQuery->createParameter('id')))
			->where($deleteQuery->expr()->eq('group_name', $deleteQuery->createParameter('group_name')))
			->where($deleteQuery->expr()->eq('value', $deleteQuery->createParameter('value')));

		do {
			$this->connection->beginTransaction();

			$results = $selectQuery->executeQuery();

			while ($row = $results->fetch()) {
				$metadata = $this->filesMetadataManager->getMetadata($row['id'], true);

				switch ($row['group_name']) {
					case 'size':
						$metadata->setArray('photos-size', json_decode($row['value'], true));
						break;
					case 'gps':
						$metadata->setArray('photos-gps', json_decode($row['value'], true));
						break;
					case 'photos_place':
						$metadata->setString('photos-place', $row['value'], true);
						break;
				}

				$this->filesMetadataManager->saveMetadata($metadata);
				$deleteQuery->setParameter('id', $row['id']);
				$deleteQuery->setParameter('group_name', $row['group_name']);
				$deleteQuery->setParameter('value', $row['value']);
				$deleteQuery->executeStatement();
			}

			$results->closeCursor();

			$this->connection->commit();

			// Stop if execution time is more than one hour.
			if (time() - $startTime > 60 * 60) {
				return;
			}
		} while ($results->rowCount() !== 0);

		$this->connection->dropTable('file_metadata');
		$this->jobList->remove(MigrateMetadataJob::class);
	}
}
