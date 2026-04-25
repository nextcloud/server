<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Provides a process-local cache of persisted storage-id mappings.
 *
 * Caches lookups in both directions:
 * - string storage id to storage record
 * - numeric storage id to storage record
 *
 * This class reduces repeated database lookups for storage mapping metadata
 * and provides invalidation helpers for callers that update those records.
 */
class StorageGlobal {
	/** @var array<string, array{id: string, numeric_id: int, available: bool, last_checked: int}> */
	private array $cache = [];

	/** @var array<int, array{id: string, numeric_id: int, available: bool, last_checked: int}> */
	private array $numericIdCache = [];

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * @param string[] $storageIds
	 */
	public function loadForStorageIds(array $storageIds): void {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['id', 'numeric_id', 'available', 'last_checked'])
			->from('storages')
			->where($builder->expr()->in('id', $builder->createNamedParameter(array_values($storageIds), IQueryBuilder::PARAM_STR_ARRAY)));

		$result = $query->executeQuery();
		while (($row = $result->fetch()) !== false) {
			$normalizedRow = [
				'id' => (string)$row['id'],
				'numeric_id' => (int)$row['numeric_id'],
				'available' => (bool)$row['available'],
				'last_checked' => (int)$row['last_checked'],
			];

			$this->cache[$normalizedRow['id']] = $normalizedRow;
		}

		$result->closeCursor();
	}

	/**
	 * @return array{id: string, numeric_id: int, available: bool, last_checked: int}|null
	 */
	public function getStorageInfo(string $storageId): ?array {
		if (!isset($this->cache[$storageId])) {
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->select(['id', 'numeric_id', 'available', 'last_checked'])
				->from('storages')
				->where($builder->expr()->eq('id', $builder->createNamedParameter($storageId)));

			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();

			if ($row !== false) {
				$normalizedRow = [
					'id' => (string)$row['id'],
					'numeric_id' => (int)$row['numeric_id'],
					'available' => (bool)$row['available'],
					'last_checked' => (int)$row['last_checked'],
				];

				$this->cache[$storageId] = $normalizedRow;
				$this->numericIdCache[$normalizedRow['numeric_id']] = $normalizedRow;
			}
		}

		return $this->cache[$storageId] ?? null;
	}

	/**
	 * @return array{id: string, numeric_id: int, available: bool, last_checked: int}|null
	 */
	public function getStorageInfoByNumericId(int $numericId): ?array {
		if (!isset($this->numericIdCache[$numericId])) {
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->select(['id', 'numeric_id', 'available', 'last_checked'])
				->from('storages')
				->where($builder->expr()->eq('numeric_id', $builder->createNamedParameter($numericId)));

			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();

			if ($row !== false) {
				$normalizedRow = [
					'id' => (string)$row['id'],
					'numeric_id' => (int)$row['numeric_id'],
					'available' => (bool)$row['available'],
					'last_checked' => (int)$row['last_checked'],
				];

				$this->numericIdCache[$numericId] = $normalizedRow;
				$this->cache[$normalizedRow['id']] = $normalizedRow;
			}
		}

		return $this->numericIdCache[$numericId] ?? null;
	}

	public function clearStorageInfo(string $storageId): void {
		$row = $this->cache[$storageId] ?? null;
		unset($this->cache[$storageId]);

		if ($row !== null) {
			unset($this->numericIdCache[$row['numeric_id']]);
		}
	}

	public function clearStorageInfoByNumericId(int $numericId): void {
		$row = $this->numericIdCache[$numericId] ?? null;
		unset($this->numericIdCache[$numericId]);

		if ($row !== null) {
			unset($this->cache[$row['id']]);
		}
	}

	public function clearCache(): void {
		$this->cache = [];
		$this->numericIdCache = [];
	}
}
