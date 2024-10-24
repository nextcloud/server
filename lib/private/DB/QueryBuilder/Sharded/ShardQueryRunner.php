<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OC\DB\ArrayResult;
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
	 * Try to get the shards that the keys are likely to be in, based on the shard the row was created
	 *
	 * @param int[] $primaryKeys
	 * @return int[]
	 */
	private function getLikelyShards(array $primaryKeys): array {
		$shards = [];
		foreach ($primaryKeys as $primaryKey) {
			$encodedShard = $primaryKey & ShardDefinition::PRIMARY_KEY_SHARD_MASK;
			if ($encodedShard < count($this->shardDefinition->shards) && !in_array($encodedShard, $shards)) {
				$shards[] = $encodedShard;
			}
		}
		return $shards;
	}

	/**
	 * Execute a SELECT statement across the configured shards
	 *
	 * @param IQueryBuilder $query
	 * @param bool $allShards
	 * @param int[] $shardKeys
	 * @param int[] $primaryKeys
	 * @param array{column: string, order: string}[] $sortList
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return IResult
	 */
	public function executeQuery(
		IQueryBuilder $query,
		bool $allShards,
		array $shardKeys,
		array $primaryKeys,
		?array $sortList = null,
		?int $limit = null,
		?int $offset = null,
	): IResult {
		$shards = $this->getShards($allShards, $shardKeys);
		$results = [];
		if ($shards && count($shards) === 1) {
			// trivial case
			return $query->executeQuery($this->shardConnectionManager->getConnection($this->shardDefinition, $shards[0]));
		}
		// we have to emulate limit and offset, so we select offset+limit from all shards to ensure we have enough rows
		// and then filter them down after we merged the results
		if ($limit !== null && $offset !== null) {
			$query->setMaxResults($limit + $offset);
		}

		if ($shards) {
			// we know exactly what shards we need to query
			foreach ($shards as $shard) {
				$shardConnection = $this->shardConnectionManager->getConnection($this->shardDefinition, $shard);
				$subResult = $query->executeQuery($shardConnection);
				$results = array_merge($results, $subResult->fetchAll());
				$subResult->closeCursor();
			}
		} else {
			// we don't know for sure what shards we need to query,
			// we first try the shards that are "likely" to have the rows we want, based on the shard that the row was
			// originally created in. If we then still haven't found all rows we try the rest of the shards
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
					// we have all the rows we're looking for
					break;
				}
			}
		}

		if ($sortList) {
			usort($results, function ($a, $b) use ($sortList) {
				foreach ($sortList as $sort) {
					$valueA = $a[$sort['column']] ?? null;
					$valueB = $b[$sort['column']] ?? null;
					$cmp = $valueA <=> $valueB;
					if ($cmp === 0) {
						continue;
					}
					if ($sort['order'] === 'DESC') {
						$cmp = -$cmp;
					}
					return $cmp;
				}
			});
		}

		if ($limit !== null && $offset !== null) {
			$results = array_slice($results, $offset, $limit);
		} elseif ($limit !== null) {
			$results = array_slice($results, 0, $limit);
		} elseif ($offset !== null) {
			$results = array_slice($results, $offset);
		}

		return new ArrayResult($results);
	}

	/**
	 * Execute an UPDATE or DELETE statement
	 *
	 * @param IQueryBuilder $query
	 * @param bool $allShards
	 * @param int[] $shardKeys
	 * @param int[] $primaryKeys
	 * @return int
	 * @throws \OCP\DB\Exception
	 */
	public function executeStatement(IQueryBuilder $query, bool $allShards, array $shardKeys, array $primaryKeys): int {
		if ($query->getType() === \Doctrine\DBAL\Query\QueryBuilder::INSERT) {
			throw new \Exception('insert queries need special handling');
		}

		$shards = $this->getShards($allShards, $shardKeys);
		$maxCount = count($primaryKeys);
		if ($shards && count($shards) === 1) {
			return $query->executeStatement($this->shardConnectionManager->getConnection($this->shardDefinition, $shards[0]));
		} elseif ($shards) {
			$maxCount = PHP_INT_MAX;
		} else {
			// sort the likely shards before the rest, similar logic to `self::executeQuery`
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
