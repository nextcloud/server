<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OC\DB\ArrayResult;
use OC\DB\QueryBuilder\QueryBuilder;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Logic for running a query across a number of shards, combining the results
 */
class ShardQueryRunner {
	public function __construct(
		private ShardConnectionManager $shardConnectionManager,
		private ShardDefinition $shardDefinition,
	) {
	}

	/**
	 * Get the shards for a specific query or null if the shards aren't known in advance
	 *
	 * @param bool $allShards
	 * @param int[] $shardKeys
	 * @return null|int[]
	 */
	public function getShards(bool $allShards, array $shardKeys): ?array {
		if ($allShards) {
			return $this->shardDefinition->getAllShards();
		}
		$allConfiguredShards = $this->shardDefinition->getAllShards();
		if (count($allConfiguredShards) === 1) {
			return $allConfiguredShards;
		}
		if (empty($shardKeys)) {
			return null;
		}
		$shards = array_map(function ($shardKey) {
			return $this->shardDefinition->getShardForKey((int)$shardKey);
		}, $shardKeys);
		return array_values(array_unique($shards));
	}

	/**
	 * Try to get the shards that the keys are likely to be in, based on the shard the row was created on and caching
	 *
	 * @param int[] $primaryKeys
	 * @return int[]
	 */
	private function getLikelyShards(array $primaryKeys): array {
		// todo
		return [];
	}

	public function executeQuery(IQueryBuilder $query, bool $allShards, array $shardKeys, array $primaryKeys): IResult {
		$shards = $this->getShards($allShards, $shardKeys);
		$results = [];
		if ($shards && count($shards) === 1) {
			return $query->executeQuery($this->shardConnectionManager->getConnection($this->shardDefinition, $shards[0]));
		} elseif ($shards) {
			foreach ($shards as $shard) {
				$shardConnection = $this->shardConnectionManager->getConnection($this->shardDefinition, $shard);
				$subResult = $query->executeQuery($shardConnection);
				$results = array_merge($results, $subResult->fetchAll());
				$subResult->closeCursor();
			}
			return new ArrayResult($results);
		} else {
			// sort the likely shards before the rest
			$likelyShards = $this->getLikelyShards($primaryKeys);
			$unlikelyShards = array_diff($this->shardDefinition->getAllShards(), $likelyShards);
			$shards = array_merge($likelyShards, $unlikelyShards);

			foreach ($shards as $shard) {
				$shardConnection = $this->shardConnectionManager->getConnection($this->shardDefinition, $shard);
				$subResult = $query->executeQuery($shardConnection);
				$rows = $subResult->fetchAll();
				$results = array_merge($results, $rows);
				$subResult->closeCursor();

				if (count($rows) >= count($primaryKeys)) {
					break;
				}
			}
			return new ArrayResult($results);
		}
	}

	private function getKeyFromRow(array $row): int {
		$key = null;
		if (isset($row[$this->shardDefinition->primaryKey])) {
			$key = $row[$this->shardDefinition->primaryKey];
		} else {
			foreach ($this->shardDefinition->companionKeys as $companionKey) {
				if (isset($row[$companionKey])) {
					$key = $row[$companionKey];
					break;
				}
			}
		}
		if ($key === null) {
			throw new InvalidShardedQueryException("No primary key returned for sharded query");
		}
		return $key;
	}

	public function executeStatement(IQueryBuilder $query, bool $allShards, array $shardKeys, array $primaryKeys): int {
		if ($query->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
			throw new \Exception("insert queries need special handling");
		}

		$shards = $this->getShards($allShards, $shardKeys);
		$maxCount = count($primaryKeys);
		if ($shards && count($shards) === 1) {
			return $query->executeStatement($this->shardConnectionManager->getConnection($this->shardDefinition, $shards[0]));
		} elseif ($shards) {
			$maxCount = PHP_INT_MAX;
		} else {
			// sort the likely shards before the rest
			$likelyShards = $this->getLikelyShards($primaryKeys);
			$unlikelyShards = array_diff($this->shardDefinition->getAllShards(), $likelyShards);
			$shards = array_merge($likelyShards, $unlikelyShards);
		}

		$count = 0;

		foreach ($shards as $shard) {
			$shardConnection = $this->shardConnectionManager->getConnection($this->shardDefinition, $shard);
			$count += $query->executeStatement($shardConnection);

			if ($count >= $maxCount) {
				break;
			}
		}
		return $count;
	}
}
