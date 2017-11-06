<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

	/**
	 * @return \Generator
	 * @suppress SqlInjectionChecker
	 */
	private function getInvalidEntries() {
		$builder = $this->connection->getQueryBuilder();

		$computedPath = $builder->func()->concat(
			'p.path',
			$builder->func()->concat($builder->createNamedParameter('/'), 'f.name')
		);

		//select f.path, f.parent,p.path from oc_filecache f inner join oc_filecache p on f.parent=p.fileid and p.path!='' where f.path != p.path || '/' || f.name;
		$builder->select('f.fileid', 'f.path', 'f.name', 'f.parent', 'f.storage')
			->selectAlias('p.path', 'parent_path')
			->selectAlias('p.storage', 'parent_storage')
			->from('filecache', 'f')
			->innerJoin('f', 'filecache', 'p', $builder->expr()->andX(
				$builder->expr()->eq('f.parent', 'p.fileid'),
				$builder->expr()->nonEmptyString('p.name')
			))
			->where($builder->expr()->neq('f.path', $computedPath))
			->setMaxResults(self::MAX_ROWS);

		do {
			$result = $builder->execute();
			$rows = $result->fetchAll();
			foreach ($rows as $row) {
				yield $row;
			}
			$result->closeCursor();
		} while (count($rows) > 0);
	}

	private function getId($storage, $path) {
		if (!$this->getIdQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->getIdQuery = $builder->select('fileid')
				->from('filecache')
				->where($builder->expr()->eq('storage', $builder->createParameter('storage')))
				->andWhere($builder->expr()->eq('path_hash', $builder->createParameter('path_hash')));
		}

		$this->getIdQuery->setParameter('storage', $storage, IQueryBuilder::PARAM_INT);
		$this->getIdQuery->setParameter('path_hash', md5($path));

		return $this->getIdQuery->execute()->fetchColumn();
	}

	/**
	 * @param string $fileid
	 * @param string $newPath
	 * @param string $newStorage
	 * @suppress SqlInjectionChecker
	 */
	private function update($fileid, $newPath, $newStorage) {
		if (!$this->updateQuery) {
			$builder = $this->connection->getQueryBuilder();

			$this->updateQuery = $builder->update('filecache')
				->set('path', $builder->createParameter('newpath'))
				->set('path_hash', $builder->func()->md5($builder->createParameter('newpath')))
				->set('storage', $builder->createParameter('newstorage'))
				->where($builder->expr()->eq('fileid', $builder->createParameter('fileid')));
		}

		$this->updateQuery->setParameter('newpath', $newPath);
		$this->updateQuery->setParameter('newstorage', $newStorage);
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
			if ($newId = $this->getId($entry['parent_storage'], $calculatedPath)) {
				// a new entry with the correct path has already been created, reuse that one and delete the incorrect entry
				$this->reparent($entry['fileid'], $newId);
				$this->delete($entry['fileid']);
			} else {
				$this->update($entry['fileid'], $calculatedPath, $entry['parent_storage']);
			}
		}
		$this->connection->commit();
		return $count;
	}

	private function shouldRun() {
		$versionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');

		// was added to 11.0.5.2, 12.0.0.30 and 13.0.0.1
		$shouldRun = version_compare($versionFromBeforeUpdate, '11.0.5.2', '<');
		$shouldRun |= version_compare($versionFromBeforeUpdate, '12.0.0.0', '>=') && version_compare($versionFromBeforeUpdate, '12.0.0.30', '<');
		$shouldRun |= version_compare($versionFromBeforeUpdate, '13.0.0.0', '==');
		return $shouldRun;
	}

	public function run(IOutput $output) {
		if ($this->shouldRun()) {
			$count = $this->repair();

			$output->info('Repaired ' . $count . ' paths');
		}
	}
}
