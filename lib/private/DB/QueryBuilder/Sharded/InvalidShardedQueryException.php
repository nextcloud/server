<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder\Sharded;

/**
 * Queries on sharded table has the following limitations:
 *
 * 1. Either the shard key (e.g. "storage") or primary key (e.g. "fileid") must be mentioned in the query.
 *    Or the query must be explicitly marked as running across all shards.
 *
 *    For queries where it isn't possible to set one of these keys in the query normally, you can set it using `hintShardKey`
 *
 * 2. Insert statements must always explicitly set the shard key
 * 3. A query on a sharded table is not allowed to join on the same table
 * 4. Right joins are not allowed on sharded tables
 * 5. Updating the shard key where the new shard key maps to a different shard is not allowed
 *
 *    Moving rows to a different shard needs to be implemented manually. `CrossShardMoveHelper` provides
 *    some tools to help make this easier.
 */
class InvalidShardedQueryException extends \Exception {

}
