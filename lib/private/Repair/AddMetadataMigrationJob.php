<?php
/**
 * @copyright Copyright (c) 2024 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair;

use OC\Core\BackgroundJobs\MetadataMigrationJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddMetadataMigrationJob implements IRepairStep {
	public function __construct(
		private IJobList $jobList,
		private IDBConnection $db,
		private IConfig $config,
	) {
	}

	public function getName() {
		return 'Queue a job to migrate the file_metadata table and delete the metadata column once empty';
	}

	public function run(IOutput $output) {
		$schema = $this->db->createSchema();

		$prefix = $this->config->getSystemValueString('dbtableprefix', 'oc_');
		$metadataTable = $schema->getTable($prefix.'file_metadata');

		if (!$metadataTable->hasColumn('metadata')) {
			return;
		}

		$selectQuery = $this->db->getQueryBuilder();
		$result = $selectQuery->select('id', 'group_name', 'metadata')
			->from('file_metadata')
			->where($selectQuery->expr()->nonEmptyString('metadata'))
			->setMaxResults(1)
			->executeQuery();

		if ($result->rowCount() === 0) {
			$output->info('Removing metadata column from the file_metadata table.');
			$metadataTable->dropColumn('metadata');
			$this->db->migrateToSchema($schema);
			return;
		}

		if ($this->jobList->has(MetadataMigrationJob::class, null)) {
			return;
		}

		$this->jobList->add(MetadataMigrationJob::class);
	}
}
