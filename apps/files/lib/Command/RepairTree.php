<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
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

namespace OCA\Files\Command;

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
		$rows = $this->findBrokenTreeBits();
		$fix = !$input->getOption('dry-run');

		$output->writeln("Found " . count($rows) . " file entries with an invalid path");

		if ($fix) {
			$this->connection->beginTransaction();
		}

		$query = $this->connection->getQueryBuilder();
		$query->update('filecache')
			->set('path', $query->createParameter('path'))
			->set('path_hash', $query->func()->md5($query->createParameter('path')))
			->where($query->expr()->eq('fileid', $query->createParameter('fileid')));

		foreach ($rows as $row) {
			$output->writeln("Path of file ${row['fileid']} is ${row['path']} but should be ${row['parent_path']}/${row['name']} based on it's parent", OutputInterface::VERBOSITY_VERBOSE);

			if ($fix) {
				$query->setParameters([
					'fileid' => $row['fileid'],
					'path' => $row['parent_path'] . '/' . $row['name'],
				]);
				$query->execute();
			}
		}

		if ($fix) {
			$this->connection->commit();
		}

		return 0;
	}

	private function findBrokenTreeBits(): array {
		$query = $this->connection->getQueryBuilder();

		$query->select('f.fileid', 'f.path', 'f.parent', 'f.name')
			->selectAlias('p.path', 'parent_path')
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
			));

		return $query->execute()->fetchAll();
	}
}
