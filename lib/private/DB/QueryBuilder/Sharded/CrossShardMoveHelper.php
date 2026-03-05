<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Utility methods for implementing logic that moves data across shards
 */
class CrossShardMoveHelper {
	public function __construct(
		private ShardConnectionManager $connectionManager,
	) {
	}

	public function getConnection(ShardDefinition $shardDefinition, int $shardKey): IDBConnection {
		return $this->connectionManager->getConnection($shardDefinition, $shardDefinition->getShardForKey($shardKey));
	}

	/**
	 * Update the shard key of a set of rows, moving them to a different shard if needed
	 *
	 * @param ShardDefinition $shardDefinition
	 * @param string $table
	 * @param string $shardColumn
	 * @param int $sourceShardKey
	 * @param int $targetShardKey
	 * @param string $primaryColumn
	 * @param int[] $primaryKeys
	 * @return void
	 */
	public function moveCrossShards(ShardDefinition $shardDefinition, string $table, string $shardColumn, int $sourceShardKey, int $targetShardKey, string $primaryColumn, array $primaryKeys): void {
		$sourceShard = $shardDefinition->getShardForKey($sourceShardKey);
		$targetShard = $shardDefinition->getShardForKey($targetShardKey);
		$sourceConnection = $this->connectionManager->getConnection($shardDefinition, $sourceShard);
		if ($sourceShard === $targetShard) {
			$this->updateItems($sourceConnection, $table, $shardColumn, $targetShardKey, $primaryColumn, $primaryKeys);

			return;
		}
		$targetConnection = $this->connectionManager->getConnection($shardDefinition, $targetShard);

		$sourceItems = $this->loadItems($sourceConnection, $table, $primaryColumn, $primaryKeys);
		foreach ($sourceItems as &$sourceItem) {
			$sourceItem[$shardColumn] = $targetShardKey;
		}
		if (!$sourceItems) {
			return;
		}

		$sourceConnection->beginTransaction();
		$targetConnection->beginTransaction();
		try {
			$this->saveItems($targetConnection, $table, $sourceItems);
			$this->deleteItems($sourceConnection, $table, $primaryColumn, $primaryKeys);

			$targetConnection->commit();
			$sourceConnection->commit();
		} catch (\Exception $e) {
			$sourceConnection->rollback();
			$targetConnection->rollback();
			throw $e;
		}
	}

	/**
	 * Load rows from a table to move
	 *
	 * @param IDBConnection $connection
	 * @param string $table
	 * @param string $primaryColumn
	 * @param int[] $primaryKeys
	 * @return array[]
	 */
	public function loadItems(IDBConnection $connection, string $table, string $primaryColumn, array $primaryKeys): array {
		$query = $connection->getQueryBuilder();
		$query->select('*')
			->from($table)
			->where($query->expr()->in($primaryColumn, $query->createParameter('keys')));

		$chunks = array_chunk($primaryKeys, 1000);

		$results = [];
		foreach ($chunks as $chunk) {
			$query->setParameter('keys', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$results = array_merge($results, $query->executeQuery()->fetchAll());
		}

		return $results;
	}

	/**
	 * Save modified rows
	 *
	 * @param IDBConnection $connection
	 * @param string $table
	 * @param array[] $items
	 * @return void
	 */
	public function saveItems(IDBConnection $connection, string $table, array $items): void {
		if (count($items) === 0) {
			return;
		}
		$query = $connection->getQueryBuilder();
		$query->insert($table);
		foreach ($items[0] as $column => $value) {
			$query->setValue($column, $query->createParameter($column));
		}

		foreach ($items as $item) {
			foreach ($item as $column => $value) {
				if (is_int($column)) {
					$query->setParameter($column, $value, IQueryBuilder::PARAM_INT);
				} else {
					$query->setParameter($column, $value);
				}
			}
			$query->executeStatement();
		}
	}

	/**
	 * @param IDBConnection $connection
	 * @param string $table
	 * @param string $primaryColumn
	 * @param int[] $primaryKeys
	 * @return void
	 */
	public function updateItems(IDBConnection $connection, string $table, string $shardColumn, int $targetShardKey, string $primaryColumn, array $primaryKeys): void {
		$query = $connection->getQueryBuilder();
		$query->update($table)
			->set($shardColumn, $query->createNamedParameter($targetShardKey, IQueryBuilder::PARAM_INT))
			->where($query->expr()->in($primaryColumn, $query->createNamedParameter($primaryKeys, IQueryBuilder::PARAM_INT_ARRAY)));
		$query->executeQuery()->fetchAll();
	}

	/**
	 * @param IDBConnection $connection
	 * @param string $table
	 * @param string $primaryColumn
	 * @param int[] $primaryKeys
	 * @return void
	 */
	public function deleteItems(IDBConnection $connection, string $table, string $primaryColumn, array $primaryKeys): void {
		$query = $connection->getQueryBuilder();
		$query->delete($table)
			->where($query->expr()->in($primaryColumn, $query->createParameter('keys')));
		$chunks = array_chunk($primaryKeys, 1000);

		foreach ($chunks as $chunk) {
			$query->setParameter('keys', $chunk, IQueryBuilder::PARAM_INT_ARRAY);
			$query->executeStatement();
		}
	}
}
