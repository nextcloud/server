<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
			->setDescription('Clean up orphaned filecache and mount entries')
			->setHelp('Deletes orphaned filecache and mount entries (those without an existing storage).')
			->addOption('skip-filecache-extended', null, InputOption::VALUE_NONE, 'don\'t remove orphaned entries from filecache_extended');
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$fileIdsByStorage = [];

		$deletedStorages = array_diff($this->getReferencedStorages(), $this->getExistingStorages());

		$deleteExtended = !$input->getOption('skip-filecache-extended');
		if ($deleteExtended) {
			$fileIdsByStorage = $this->getFileIdsForStorages($deletedStorages);
		}

		$deletedEntries = $this->cleanupOrphanedFileCache($deletedStorages);
		$output->writeln("$deletedEntries orphaned file cache entries deleted");

		if ($deleteExtended) {
			$deletedFileCacheExtended = $this->cleanupOrphanedFileCacheExtended($fileIdsByStorage);
			$output->writeln("$deletedFileCacheExtended orphaned file cache extended entries deleted");
		}

		$deletedMounts = $this->cleanupOrphanedMounts();
		$output->writeln("$deletedMounts orphaned mount entries deleted");

		return self::SUCCESS;
	}

	private function getReferencedStorages(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('storage')
			->from('filecache')
			->groupBy('storage')
			->runAcrossAllShards();
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
	}

	private function getExistingStorages(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('numeric_id')
			->from('storages')
			->groupBy('numeric_id');
		return $query->executeQuery()->fetchAll(\PDO::FETCH_COLUMN);
	}

	/**
	 * @param int[] $storageIds
	 * @return array<int, int[]>
	 */
	private function getFileIdsForStorages(array $storageIds): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('storage', 'fileid')
			->from('filecache')
			->where($query->expr()->in('storage', $query->createParameter('storage_ids')));

		$result = [];
		$storageIdChunks = array_chunk($storageIds, self::CHUNK_SIZE);
		foreach ($storageIdChunks as $storageIdChunk) {
			$query->setParameter('storage_ids', $storageIdChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$chunk = $query->executeQuery()->fetchAll();
			foreach ($chunk as $row) {
				$result[$row['storage']][] = $row['fileid'];
			}
		}
		return $result;
	}

	private function cleanupOrphanedFileCache(array $deletedStorages): int {
		$deletedEntries = 0;

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('filecache')
			->where($deleteQuery->expr()->in('storage', $deleteQuery->createParameter('storage_ids')));

		$deletedStorageChunks = array_chunk($deletedStorages, self::CHUNK_SIZE);
		foreach ($deletedStorageChunks as $deletedStorageChunk) {
			$deleteQuery->setParameter('storage_ids', $deletedStorageChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$deletedEntries += $deleteQuery->executeStatement();
		}

		return $deletedEntries;
	}

	/**
	 * @param array<int, int[]> $fileIdsByStorage
	 * @return int
	 */
	private function cleanupOrphanedFileCacheExtended(array $fileIdsByStorage): int {
		$deletedEntries = 0;

		$deleteQuery = $this->connection->getQueryBuilder();
		$deleteQuery->delete('filecache_extended')
			->where($deleteQuery->expr()->in('fileid', $deleteQuery->createParameter('file_ids')));

		foreach ($fileIdsByStorage as $storageId => $fileIds) {
			$deleteQuery->hintShardKey('storage', $storageId, true);
			$fileChunks = array_chunk($fileIds, self::CHUNK_SIZE);
			foreach ($fileChunks as $fileChunk) {
				$deleteQuery->setParameter('file_ids', $fileChunk, IQueryBuilder::PARAM_INT_ARRAY);
				$deletedEntries += $deleteQuery->executeStatement();
			}
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
			$result = $query->executeQuery();
			while ($row = $result->fetch()) {
				$deletedInLastChunk++;
				$deletedEntries += $deleteQuery->setParameter('storageid', (int)$row['storage_id'])
					->executeStatement();
			}
			$result->closeCursor();
		}

		return $deletedEntries;
	}
}
