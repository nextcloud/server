<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Repair\NC13;


use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairInvalidPaths implements IRepairStep {
	/** @var IDBConnection */
	private $connection;
	/** @var IConfig */
	private $config;

	public function __construct(IDBConnection $connection, IConfig $config) {
		$this->connection = $connection;
		$this->config = $config;
	}


	public function getName() {
		return 'Repair invalid paths in file cache';
	}

	private function getInvalidEntries() {
		$builder = $this->connection->getQueryBuilder();

		$computedPath = $builder->func()->concat(
			'p.path',
			$builder->func()->concat($builder->createNamedParameter('/'), 'f.name')
		);

		//select f.path, f.parent,p.path from oc_filecache f inner join oc_filecache p on f.parent=p.fileid and p.path!='' where f.path != p.path || '/' || f.name;
		$query = $builder->select('f.fileid', 'f.path', 'p.path AS parent_path', 'f.name', 'f.parent', 'f.storage')
			->from('filecache', 'f')
			->innerJoin('f', 'filecache', 'p', $builder->expr()->andX(
				$builder->expr()->eq('f.parent', 'p.fileid'),
				$builder->expr()->neq('p.name', $builder->createNamedParameter(''))
			))
			->where($builder->expr()->neq('f.path', $computedPath));

		return $query->execute()->fetchAll();
	}

	private function getId($storage, $path) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->select('fileid')
			->from('filecache')
			->where($builder->expr()->eq('storage', $builder->createNamedParameter($storage)))
			->andWhere($builder->expr()->eq('path', $builder->createNamedParameter($path)));

		return $query->execute()->fetchColumn();
	}

	private function update($fileid, $newPath) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->update('filecache')
			->set('path', $builder->createNamedParameter($newPath))
			->set('path_hash', $builder->createNamedParameter(md5($newPath)))
			->where($builder->expr()->eq('fileid', $builder->createNamedParameter($fileid)));

		$query->execute();
	}

	private function reparent($from, $to) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->update('filecache')
			->set('parent', $builder->createNamedParameter($to))
			->where($builder->expr()->eq('fileid', $builder->createNamedParameter($from)));
		$query->execute();
	}

	private function delete($fileid) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->delete('filecache')
			->where($builder->expr()->eq('fileid', $builder->createNamedParameter($fileid)));
		$query->execute();
	}

	private function repair() {
		$entries = $this->getInvalidEntries();
		foreach ($entries as $entry) {
			$calculatedPath = $entry['parent_path'] . '/' . $entry['name'];
			if ($newId = $this->getId($entry['storage'], $calculatedPath)) {
				// a new entry with the correct path has already been created, reuse that one and delete the incorrect entry
				$this->reparent($entry['fileid'], $newId);
				$this->delete($entry['fileid']);
			} else {
				$this->update($entry['fileid'], $calculatedPath);
			}
		}
		return count($entries);
	}

	public function run(IOutput $output) {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');
		// was added to 12.0.0.30 and 13.0.0.1
		if (version_compare($versionFromBeforeUpdate, '12.0.0.30', '<') || version_compare($versionFromBeforeUpdate, '13.0.0.0', '==')) {
			$count = $this->repair();

			$output->info('Repaired ' . $count . ' paths');
		}
	}
}
