<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files\Command;

use OCP\Console\Attribute\AsCommand;
use OCP\Console\Attribute\Option;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Delete all file entries that have no matching entries in the storage table,
 * and the rows keyed by file id that have no matching file entry.
 */
#[AsCommand(
	name: 'files:cleanup',
	description: 'Clean up orphaned filecache and mount entries',
	help: 'Deletes orphaned filecache and mount entries (those without an existing storage), and filecache_extended and file metadata entries without a filecache entry.',
)]
class DeleteOrphanedFiles {
	public const int CHUNK_SIZE = 200;

	public function __construct(
		protected readonly IDBConnection $connection,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Option(name: 'skip-filecache-extended', description: 'don\'t remove orphaned entries from filecache_extended')]
		bool $skipFilecacheExtended = false,
	): ExitCode {
		$deletedStorages = array_diff($this->getReferencedStorages(), $this->getExistingStorages());

		$deletedEntries = $this->cleanupOrphanedFileCache($deletedStorages);
		$output->writeln("$deletedEntries orphaned file cache entries deleted");

		if (!$skipFilecacheExtended) {
			$deletedFileCacheExtended = $this->cleanupEntriesWithoutFileCache('filecache_extended', 'fileid');
			$output->writeln("$deletedFileCacheExtended orphaned file cache extended entries deleted");
		}

		$deletedMetadata = $this->cleanupEntriesWithoutFileCache('files_metadata', 'file_id');
		$output->writeln("$deletedMetadata orphaned file metadata entries deleted");

		$deletedMetadataIndex = $this->cleanupEntriesWithoutFileCache('files_metadata_index', 'file_id');
		$output->writeln("$deletedMetadataIndex orphaned file metadata index entries deleted");

		$deletedMounts = $this->cleanupOrphanedMounts();
		$output->writeln("$deletedMounts orphaned mount entries deleted");

		return ExitCode::Success;
	}

	private function getReferencedStorages(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('storage')
			->from('filecache')
			->groupBy('storage')
			->runAcrossAllShards();
		return $query->executeQuery()->fetchFirstColumn();
	}

	private function getExistingStorages(): array {
		$query = $this->connection->getQueryBuilder();
		$query->select('numeric_id')
			->from('storages')
			->groupBy('numeric_id');
		return $query->executeQuery()->fetchFirstColumn();
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

	private function cleanupEntriesWithoutFileCache(string $table, string $fileIdColumn): int {
		$deletedEntries = 0;
		$lastFileId = 0;

		while (true) {
			$query = $this->connection->getQueryBuilder();
			$query->select($fileIdColumn)
				->from($table)
				->where($query->expr()->gt($fileIdColumn, $query->createNamedParameter($lastFileId, IQueryBuilder::PARAM_INT)))
				->orderBy($fileIdColumn)
				->setMaxResults(IQueryBuilder::MAX_IN_PARAMETERS)
				->runAcrossAllShards();
			$fileIds = array_unique(array_map(intval(...), $query->executeQuery()->fetchFirstColumn()));
			if ($fileIds === []) {
				return $deletedEntries;
			}

			$query = $this->connection->getQueryBuilder();
			$query->select('fileid')
				->from('filecache')
				->where($query->expr()->in('fileid', $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));
			$missingFileIds = array_diff($fileIds, $query->executeQuery()->fetchFirstColumn());

			if ($missingFileIds !== []) {
				$query = $this->connection->getQueryBuilder();
				$query->delete($table)
					->where($query->expr()->in($fileIdColumn, $query->createNamedParameter($missingFileIds, IQueryBuilder::PARAM_INT_ARRAY)));
				$deletedEntries += $query->executeStatement();
			}

			$lastFileId = max($fileIds);
		}
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
			while ($row = $result->fetchAssociative()) {
				$deletedInLastChunk++;
				$deletedEntries += $deleteQuery->setParameter('storageid', (int)$row['storage_id'])
					->executeStatement();
			}
			$result->closeCursor();
		}

		return $deletedEntries;
	}
}
