<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Command;

use OC\DB\QueryBuilder\Literal;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepairTree extends Command {
	public const CHUNK_SIZE = 200;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('files:repair-tree')
			->setDescription('Try and repair malformed filesystem tree structures')
			->addOption('dry-run');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fix = !$input->getOption('dry-run');

		$this->repairParent($fix, $output);
		$this->repairPaths($fix, $output);

		return 0;
	}

	private function repairParent(bool $fix, OutputInterface $output) {
		$rows = $this->findMissingParents();

		$output->writeln("Found " . count($rows) . " file entries with an invalid parent");

		if ($fix) {
			$this->connection->beginTransaction();
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('filecache')
			->set('parent', $query->createParameter('parent'))
			->where($query->expr()->eq('fileid', $query->createParameter('fileid')));

		foreach ($rows as $row) {
			$output->writeln("Parent of file <info>{$row['fileid']}</info> (<info>{$row['path']}</info>) is <info>-1</info> but should be <info>{$row['parent_id']}</info> based on its path", OutputInterface::VERBOSITY_VERBOSE);

			if ($fix) {
				$query->setParameters([
					'fileid' => $row['fileid'],
					'parent' => $row['parent_id'],
				]);
				$query->execute();
			}
		}

		if ($fix) {
			$this->connection->commit();
		}
	}

	private function repairPaths(bool $fix, OutputInterface $output) {
		$rows = $this->findBrokenTreeBits();

		$output->writeln("Found " . count($rows) . " file entries with an invalid path");

		if ($fix) {
			$this->connection->beginTransaction();
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('filecache')
			->set('path', $query->createParameter('path'))
			->set('path_hash', $query->func()->md5($query->createParameter('path')))
			->set('storage', $query->createParameter('storage'))
			->where($query->expr()->eq('fileid', $query->createParameter('fileid')));

		foreach ($rows as $row) {
			$output->writeln("Path of file <info>{$row['fileid']}</info> is <info>{$row['path']}</info> but should be <info>{$row['parent_path']}/{$row['name']}</info> based on its parent", OutputInterface::VERBOSITY_VERBOSE);

			if ($fix) {
				$fileId = $this->getFileId((int)$row['parent_storage'], $row['parent_path'] . '/' . $row['name']);
				if ($fileId > 0) {
					$output->writeln("Cache entry has already be recreated with id $fileId, deleting instead");
					$this->deleteById((int)$row['fileid']);
				} else {
					$query->setParameters([
						'fileid' => $row['fileid'],
						'path' => $row['parent_path'] . '/' . $row['name'],
						'storage' => $row['parent_storage'],
					]);
					$query->execute();
				}
			}
		}

		if ($fix) {
			$this->connection->commit();
		}
	}

	private function getFileId(int $storage, string $path) {
		$query = $this->connection->getQueryBuilder();
		$query->select('fileid')
			->from('filecache')
			->where($query->expr()->eq('storage', $query->createNamedParameter($storage)))
			->andWhere($query->expr()->eq('path_hash', $query->createNamedParameter(md5($path))));
		return $query->execute()->fetch(\PDO::FETCH_COLUMN);
	}

	private function deleteById(int $fileId) {
		$query = $this->connection->getQueryBuilder();
		$query->delete('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($fileId)));
		$query->execute();
	}

	private function findBrokenTreeBits(): array {
		$query = $this->connection->getQueryBuilder();

		$query->select('f.fileid', 'f.path', 'f.parent', 'f.name')
			->selectAlias('p.path', 'parent_path')
			->selectAlias('p.storage', 'parent_storage')
			->from('filecache', 'f')
			->innerJoin('f', 'filecache', 'p', $query->expr()->eq('f.parent', 'p.fileid'))
			->where($query->expr()->orX(
				$query->expr()->andX(
					$query->expr()->neq('p.path_hash', $query->createNamedParameter(md5(''))),
					$query->expr()->neq('f.path', $query->func()->concat('p.path', $query->func()->concat($query->createNamedParameter('/'), 'f.name')))
				),
				$query->expr()->andX(
					$query->expr()->eq('p.path_hash', $query->createNamedParameter(md5(''))),
					$query->expr()->neq('f.path', 'f.name')
				),
				$query->expr()->neq('f.storage', 'p.storage')
			))
			->andWhere($query->expr()->neq('f.parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));

		return $query->execute()->fetchAll();
	}

	private function findMissingParents(): array {
		$query = $this->connection->getQueryBuilder();

		// find all items with parent == -1 && path !== ''
		// and also find the matching parent item by p.path == dirname(f.path)
		//
		// we implement dirname in sql by doing substr(1, max(0, strlen(path) - strlen(name) - 1))
		//   1 because sql substr starts at 1 instead of 0
		//   -1 for the slash
		//   max(0, ...) to handle the cases where path==name

		$query->select('f.fileid', 'f.path')
			->selectAlias('p.fileid', 'parent_id')
			->from('filecache', 'f')
			->innerJoin('f', 'filecache', 'p', $query->expr()->andX(
				$query->expr()->eq(
					'p.path_hash',
					$query->func()->md5($query->func()->substring(
						'f.path',
						$query->createNamedParameter(1, IQueryBuilder::PARAM_INT),
						$query->func()->greatest(
							$query->func()->subtract(
								$query->func()->subtract(
									$query->func()->charLength('f.path'),
									$query->func()->charLength('f.name'),
								),
								$query->createNamedParameter(1, IQueryBuilder::PARAM_INT),
							),
							$query->createNamedParameter(0, IQueryBuilder::PARAM_INT),
						)

					))
				),
				$query->expr()->eq('f.storage', 'p.storage')
			))
			->where($query->expr()->neq('f.path_hash', $query->createNamedParameter(md5(''))))
			->andWhere($query->expr()->eq('f.parent', $query->createNamedParameter(-1, IQueryBuilder::PARAM_INT)));

		return $query->execute()->fetchAll();
	}
}
