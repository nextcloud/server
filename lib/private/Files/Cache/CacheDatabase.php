<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
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

namespace OC\Files\Cache;

use OC\DB\Exceptions\DbalException;
use OC\SystemConfig;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class CacheDatabase {
	private ICache $cache;

	public function __construct(
		private IDBConnection $connection, // todo: multiple db connections for sharding (open connection lazy?)
		private SystemConfig $systemConfig,
		private LoggerInterface $logger,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createLocal('storage_by_fileid');
	}

	private function connectionForStorageId(int $storage): IDBConnection {
		return $this->databaseForShard($this->getShardForStorageId($storage));
	}

	public function queryForStorageId(int $storage): CacheQueryBuilder {
		return $this->queryForShard($this->getShardForStorageId($storage));
	}

	private function databaseForShard(int $shard): IDBConnection {
		return $this->connection;
	}

	public function queryForShard(int $shard): CacheQueryBuilder {
		// todo: select db based on shard
		$query = new CacheQueryBuilder(
			$this->databaseForShard($shard),
			$this->systemConfig,
			$this->logger,
		);
		$query->setSharded(true);
		return $query;
	}

	public function getCachedStorageIdForFileId(int $fileId): ?int {
		$cached = $this->cache->get((string)$fileId);
		return ($cached === null) ? null : (int)$cached;
	}

	public function setCachedStorageIdForFileId(int $fileId, int $storageId) {
		$this->cache->set((string)$fileId, $storageId);
	}

	/**
	 * @param list<int> $fileIds
	 * @return array<int, list<int>>
	 */
	public function getCachedShardsForFileIds(array $fileIds): array {
		$result = [];
		foreach ($fileIds as $fileId) {
			$storageId = $this->getCachedStorageIdForFileId($fileId);
			if ($storageId) {
				$shard = $this->getShardForStorageId($storageId);
				$result[$shard][] = $fileId;
			}
		}
		return $result;
	}

	private function getShardForStorageId(int $storage): int {
		return 0;
	}

	/**
	 * @return list<int>
	 */
	public function getAllShards(): array {
		return [0];
	}

	public function beginTransaction(int $storageId): void {
		$this->connectionForStorageId($storageId)->beginTransaction();
	}

	public function inTransaction(int $storageId): bool {
		return $this->connectionForStorageId($storageId)->inTransaction();
	}

	public function commit(int $storageId): void {
		$this->connectionForStorageId($storageId)->commit();
	}

	public function rollBack(int $storageId): void {
		$this->connectionForStorageId($storageId)->rollBack();
	}

	/**
	 * @param List<int> $storages
	 * @return array<int, List<int>>
	 */
	private function groupStoragesByShard(array $storages): array {
		$storagesByShard = [];
		foreach ($storages as $storage) {
			$shard = $this->getShardForStorageId($storage);
			$storagesByShard[$shard][] = $storage;
		}
		return $storagesByShard;
	}

	/**
	 * Run a query against all shards for the given storage ids, combining the results.
	 *
	 * The provided callback fill be called with the query builder for each shard and the storage ids for that shard.
	 * The results from the callback will be combined and returned
	 *
	 * @template T
	 * @param List<int> $storages
	 * @param callable(CacheQueryBuilder, List<int>): T[] $callback
	 * @return T[]
	 */
	public function queryStorages(array $storages, callable $callback): array {
		$result = [];

		$storagesByShard = $this->groupStoragesByShard($storages);
		foreach($storagesByShard as $shard => $storagesForShard) {
			$query = $this->queryForShard($shard);
			$shardResults = $callback($query, $storagesForShard);
			$result += $shardResults;
		}
		return $result;
	}
}
