<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Sharded;

use OCP\DB\QueryBuilder\Sharded\IShardMapper;

/**
 * Keeps the configuration for a shard setup
 */
class ShardDefinition {
	// we reserve the bottom byte of the primary key for the initial shard
	public const MAX_SHARDS = 256;

	const PRIMARY_KEY_MASK = 0x7F_FF_FF_FF_FF_FF_FF_00;
	const PRIMARY_KEY_SHARD_MASK = 0x00_00_00_00_00_00_00_FF;
	const MAX_PRIMARY_KEY = PHP_INT_MAX >> 8;

	/**
	 * @param string $table
	 * @param string $primaryKey
	 * @param string $shardKey
	 * @param string[] $companionKeys
	 * @param IShardMapper $shardMapper
	 * @param string[] $companionTables
	 * @param array $shards
	 */
	public function __construct(
		public string $table,
		public string $primaryKey,
		public array $companionKeys,
		public string $shardKey,
		public IShardMapper $shardMapper,
		public array $companionTables = [],
		public array $shards = [],
	) {
		if (count($this->shards) >= self::MAX_SHARDS) {
			throw new \Exception("Only allowed maximum of " . self::MAX_SHARDS . " shards allowed");
		}
	}

	public function hasTable(string $table): bool {
		if ($this->table === $table) {
			return true;
		}
		return in_array($table, $this->companionTables);
	}

	public function getShardForKey(int $key): int {
		return $this->shardMapper->getShardForKey($key, count($this->shards));
	}

	public function getAllShards(): array {
		return range(0, count($this->shards) - 1);
	}

	public function isKey(string $column): bool {
		return $column === $this->primaryKey || in_array($column, $this->companionKeys);
	}
}
