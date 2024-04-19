<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
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
			$this->connection,
			$this->systemConfig,
			$this->logger,
			$this->metadataManager,
		);
	}

	public function getByFileIdInStorage(int $fileId, int $storageId): ?CacheEntry {
		$items = $this->getByFileIdsInStorage([$fileId], $storageId);
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
		$items = $this->getByFileIds([$fileId]);
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
