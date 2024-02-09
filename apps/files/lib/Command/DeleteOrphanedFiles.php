<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Command;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Delete all file entries that have no matching entries in the storage table.
 */
class DeleteOrphanedFiles extends Command {
	public const CHUNK_SIZE = 200;

	public function __construct(
		protected IDBConnection $connection,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('files:cleanup')
			->setDescription('cleanup filecache')
			->addOption('skip-filecache-extended', null, InputOption::VALUE_NONE, 'don\'t remove orphaned entries from filecache_extended');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$deletedEntries = 0;

		$query = $this->connection->getQueryBuilder();
		$query->select('fc.fileid')
			->from('filecache', 'fc')
			->where($query->expr()->isNull('s.numeric_id'))
			->leftJoin('fc', 'storages', 's', $query->expr()->eq('fc.storage', 's.numeric_id'))
			->setMaxResults(self::CHUNK_SIZE);

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('filecache')
			->where($deleteQuery->expr()->eq('fileid', $deleteQuery->createParameter('objectid')));

		$deletedInLastChunk = self::CHUNK_SIZE;
		while ($deletedInLastChunk === self::CHUNK_SIZE) {
			$deletedInLastChunk = 0;
			$result = $query->execute();
			while ($row = $result->fetch()) {
				$deletedInLastChunk++;
				$deletedEntries += $deleteQuery->setParameter('objectid', (int) $row['fileid'])
					->execute();
			}
			$result->closeCursor();
		}

		$output->writeln("$deletedEntries orphaned file cache entries deleted");

		if (!$input->getOption('skip-filecache-extended')) {
			$deletedFileCacheExtended = $this->cleanupOrphanedFileCacheExtended();
			$output->writeln("$deletedFileCacheExtended orphaned file cache extended entries deleted");
		}


		$deletedMounts = $this->cleanupOrphanedMounts();
		$output->writeln("$deletedMounts orphaned mount entries deleted");
		return self::SUCCESS;
	}

	private function cleanupOrphanedFileCacheExtended(): int {
		$deletedEntries = 0;

		$query = $this->connection->getQueryBuilder();
		$query->select('fce.fileid')
			->from('filecache_extended', 'fce')
			->leftJoin('fce', 'filecache', 'fc', $query->expr()->eq('fce.fileid', 'fc.fileid'))
			->where($query->expr()->isNull('fc.fileid'))
			->setMaxResults(self::CHUNK_SIZE);

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('filecache_extended')
			->where($deleteQuery->expr()->in('fileid', $deleteQuery->createParameter('idsToDelete')));

		$result = $query->executeQuery();
		while ($result->rowCount() > 0) {
			$idsToDelete = $result->fetchAll(\PDO::FETCH_COLUMN);

			$deleteQuery->setParameter('idsToDelete', $idsToDelete, IQueryBuilder::PARAM_INT_ARRAY);
			$deletedEntries += $deleteQuery->executeStatement();

			$result = $query->executeQuery();
		}

		return $deletedEntries;
	}

	private function cleanupOrphanedMounts(): int {
		$deletedEntries = 0;

		$query = $this->connection->getQueryBuilder();
		$query->select('m.storage_id')
			->from('mounts', 'm')
			->where($query->expr()->isNull('s.numeric_id'))
			->leftJoin('m', 'storages', 's', $query->expr()->eq('m.storage_id', 's.numeric_id'))
			->groupBy('storage_id')
			->setMaxResults(self::CHUNK_SIZE);

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('mounts')
			->where($deleteQuery->expr()->eq('storage_id', $deleteQuery->createParameter('storageid')));

		$deletedInLastChunk = self::CHUNK_SIZE;
		while ($deletedInLastChunk === self::CHUNK_SIZE) {
			$deletedInLastChunk = 0;
			$result = $query->execute();
			while ($row = $result->fetch()) {
				$deletedInLastChunk++;
				$deletedEntries += $deleteQuery->setParameter('storageid', (int) $row['storage_id'])
					->execute();
			}
			$result->closeCursor();
		}

		return $deletedEntries;
	}
}
