<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\QueryBuilder\Sharded;

/**
 * Implementation of logic of mapping shard keys to shards.
 * @since 30.0.0
 */
interface IShardMapper {
	/**
	 * Get the shard number for a given shard key and total shard count
	 *
	 * @param int $key
	 * @param int $count
	 * @return int
	 * @since 30.0.0
	 */
	public function getShardForKey(int $key, int $count): int;
}
