<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OCP\ICacheFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;

/**
 * A helper to atomically determine the next auto increment value for a sharded table
 */
class AutoIncrementHandler {
	const MIN_VALID_KEY = 1000;
	const TTL = 365 * 24 * 60 * 60;

	private ?IMemcache $cache = null;

	public function __construct(
		private ICacheFactory                  $cacheFactory,
		private ShardConnectionManager $shardConnectionManager,
	) {
		if (PHP_INT_SIZE < 8) {
			throw new \Exception("sharding is only supported with 64bit php");
		}
	}

	private function getCache(): IMemcache {
		if(is_null($this->cache)) {
			$cache = $this->cacheFactory->createDistributed("shared_autoincrement");
			if ($cache instanceof IMemcache) {
				$this->cache = $cache;
			} else {
				throw new \Exception('Distributed cache ' . get_class($this->cache) . ' is not suitable');
			}
		}
		return $this->cache;
	}

	public function getNextPrimaryKey(ShardDefinition $shardDefinition): int {
		$retries = 0;
		while ($retries < 5) {
			$next = $this->getNextPrimaryKeyInner($shardDefinition);
			if ($next !== null) {
				if ($next > ShardDefinition::MAX_PRIMARY_KEY) {
					throw new \Exception("Max primary key of " . ShardDefinition::MAX_PRIMARY_KEY . " exceeded");
				}
				return $next;
			} else {
				$retries++;
			}
		}
		throw new \Exception("Failed to get next primary key");
	}

	/**
	 * auto increment logic without retry
	 * @param ShardDefinition $shardDefinition
	 * @return int|null either the next primary key or null if the call needs to be retried
	 */
	private function getNextPrimaryKeyInner(ShardDefinition $shardDefinition): ?int {
		// because this function will likely be called concurrently from different requests
		// the implementation needs to ensure that the cached value can be cleared, invalidated or re-calculated at any point between our cache calls
		// care must be taken that the logic remains fully resilient against race conditions

		// prevent inc from returning `1` if the key doesn't exist by setting it to a non-numeric value
		$this->getCache()->add($shardDefinition->table, "empty-placeholder", self::TTL);
		$next = $this->getCache()->inc($shardDefinition->table);

		if ($this->cache instanceof IMemcacheTTL) {
			$this->getCache()->setTTL($shardDefinition->table, self::TTL);
		}

		// the "add + inc" trick above isn't strictly atomic, so as a safety we reject any result that to small
		// to handle the edge case of the stored value disappearing between the add and inc
		if (is_int($next) && $next >= self::MIN_VALID_KEY) {
			return $next;
		} elseif (is_int($next)) {
			// we hit the edge case, so invalidate the cached value
			if (!$this->getCache()->cas($shardDefinition->table, $next, "empty-placeholder")) {
				// someone else is changing the value concurrently, give up and retry
				return null;
			}
		}

		// discard the encoded initial shard
		$current = $this->getMaxFromDb($shardDefinition) >> 8;
		$next = max($current, self::MIN_VALID_KEY) + 1;
		if ($this->getCache()->cas($shardDefinition->table, "empty-placeholder", $next)) {
			return $next;
		}

		// another request set the cached value before us, so we should just be able to inc
		$next = $this->getCache()->inc($shardDefinition->table);
		if (is_int($next) && $next >= self::MIN_VALID_KEY) {
			return $next;
		} else if(is_int($next)) {
			// key got cleared, invalidate and retry
			$this->getCache()->cas($shardDefinition->table, $next, "empty-placeholder");
			return null;
		} else {
			// cleanup any non-numeric value other than the placeholder if that got stored somehow
			$this->getCache()->ncad($shardDefinition->table, "empty-placeholder");
			// retry
			return null;
		}
	}

	/**
	 * Get the maximum primary key value from the shards
	 */
	private function getMaxFromDb(ShardDefinition $shardDefinition): int {
		$max = 0;
		foreach ($shardDefinition->getAllShards() as $shard) {
			$connection = $this->shardConnectionManager->getConnection($shardDefinition, $shard);
			$query = $connection->getQueryBuilder();
			$query->select($shardDefinition->primaryKey)
				->from($shardDefinition->table)
				->orderBy($shardDefinition->primaryKey, "DESC")
				->setMaxResults(1);
			$result = $query->executeQuery()->fetchOne();
			if ($result) {
				$max = max($max, $result);
			}
		}
		return $max;
	}
}
