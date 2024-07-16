<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OC\FilesMetadata\FilesMetadataManager;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Cache\IFileAccess;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Low level access to the file cache
 */
class FileAccess implements IFileAccess {
	public function __construct(
		private IDBConnection $connection,
		private SystemConfig $systemConfig,
		private LoggerInterface $logger,
		private FilesMetadataManager $metadataManager,
		private IMimeTypeLoader $mimeTypeLoader,
	) {
	}

	private function getQuery(): CacheQueryBuilder {
		return new CacheQueryBuilder(
			$this->connection->getQueryBuilder(),
			$this->metadataManager,
		);
	}

	public function getByFileIdInStorage(int $fileId, int $storageId): ?CacheEntry {
		$items = array_values($this->getByFileIdsInStorage([$fileId], $storageId));
		return $items[0] ?? null;
	}

	public function getByPathInStorage(string $path, int $storageId): ?CacheEntry {
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->eq('filecache.path_hash', $query->createNamedParameter(md5($path))));
		$query->andWhere($query->expr()->eq('filecache.storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));

		$row = $query->executeQuery()->fetch();
		return $row ? Cache::cacheEntryFromData($row, $this->mimeTypeLoader) : null;
	}

	public function getByFileId(int $fileId): ?CacheEntry {
		$items = array_values($this->getByFileIds([$fileId]));
		return $items[0] ?? null;
	}

	/**
	 * @param array[] $rows
	 * @return array<int, CacheEntry>
	 */
	private function rowsToEntries(array $rows): array {
		$result = [];
		foreach ($rows as $row) {
			$entry = Cache::cacheEntryFromData($row, $this->mimeTypeLoader);
			$result[$entry->getId()] = $entry;
		}
		return $result;
	}

	/**
	 * @param int[] $fileIds
	 * @return array<int, CacheEntry>
	 */
	public function getByFileIds(array $fileIds): array {
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->in('filecache.fileid', $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));

		$rows = $query->executeQuery()->fetchAll();
		return $this->rowsToEntries($rows);
	}

	/**
	 * @param int[] $fileIds
	 * @param int $storageId
	 * @return array<int, CacheEntry>
	 */
	public function getByFileIdsInStorage(array $fileIds, int $storageId): array {
		$fileIds = array_values($fileIds);
		$query = $this->getQuery()->selectFileCache();
		$query->andWhere($query->expr()->in('filecache.fileid', $query->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$query->andWhere($query->expr()->eq('filecache.storage', $query->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));

		$rows = $query->executeQuery()->fetchAll();
		return $this->rowsToEntries($rows);
	}
}
