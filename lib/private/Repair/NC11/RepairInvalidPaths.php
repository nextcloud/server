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


use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RepairInvalidPaths implements IRepairStep {
	const MAX_ROWS = 1000;

	/** @var IDBConnection */
	private $connection;
	/** @var IConfig */
	private $config;

	private $getIdQuery;
	private $updateQuery;
	private $reparentQuery;
	private $deleteQuery;

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
			->where($builder->expr()->neq('f.path', $computedPath))
			->setMaxResults(self::MAX_ROWS);

		do {
			$result = $query->execute();
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				yield $row;
			}
			$result->closeCursor();
		} while (count($rows) >= self::MAX_ROWS);
	}

	private function getId($storage, $path) {
		if (!$this->getIdQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->getIdQuery = $builder->select('fileid')
				->from('filecache')
				->where($builder->expr()->eq('storage', $builder->createParameter('storage')))
				->andWhere($builder->expr()->eq('path', $builder->createParameter('path')));
		}

		$this->getIdQuery->setParameter('storage', $storage, IQueryBuilder::PARAM_INT);
		$this->getIdQuery->setParameter('path', $path);

		return $this->getIdQuery->execute()->fetchColumn();
	}

	private function update($fileid, $newPath) {
		if (!$this->updateQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->updateQuery = $builder->update('filecache')
				->set('path', $builder->createParameter('newpath'))
				->set('path_hash', $builder->func()->md5($builder->createParameter('newpath')))
				->where($builder->expr()->eq('fileid', $builder->createParameter('fileid')));
		}

		$this->updateQuery->setParameter('newpath', $newPath);
		$this->updateQuery->setParameter('fileid', $fileid, IQueryBuilder::PARAM_INT);

		$this->updateQuery->execute();
	}

	private function reparent($from, $to) {
		if (!$this->reparentQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->reparentQuery = $builder->update('filecache')
				->set('parent', $builder->createParameter('to'))
				->where($builder->expr()->eq('fileid', $builder->createParameter('from')));
		}

		$this->reparentQuery->setParameter('from', $from);
		$this->reparentQuery->setParameter('to', $to);

		$this->reparentQuery->execute();
	}

	private function delete($fileid) {
		if (!$this->deleteQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->deleteQuery = $builder->delete('filecache')
				->where($builder->expr()->eq('fileid', $builder->createParameter('fileid')));
		}

		$this->deleteQuery->setParameter('fileid', $fileid, IQueryBuilder::PARAM_INT);

		$this->deleteQuery->execute();
	}

	private function repair() {
		$this->connection->beginTransaction();
		$entries = $this->getInvalidEntries();
		$count = 0;
		foreach ($entries as $entry) {
			$count++;
			$calculatedPath = $entry['parent_path'] . '/' . $entry['name'];
			if ($newId = $this->getId($entry['storage'], $calculatedPath)) {
				// a new entry with the correct path has already been created, reuse that one and delete the incorrect entry
				$this->reparent($entry['fileid'], $newId);
				$this->delete($entry['fileid']);
			} else {
				$this->update($entry['fileid'], $calculatedPath);
			}
		}
		$this->connection->commit();
		return $count;
	}

	public function run(IOutput $output) {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');
		// was added to 11.0.5.2
		if (version_compare($versionFromBeforeUpdate, '11.0.5.2', '<')) {
			$count = $this->repair();

			$output->info('Repaired ' . $count . ' paths');
		}
	}
}
