<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder\Partitioned;

/**
 * Partitioned queries impose limitations that queries have to follow:
 *
 *  1. Any reference to columns not in the "main table" (the table referenced by "FROM"), needs to explicitly include the
 *     table or alias the column belongs to.
 *
 *     For example:
 *     ```
 *       $query->select("mount_point", "mimetype")
 *         ->from("mounts", "m")
 *         ->innerJoin("m", "filecache", "f", $query->expr()->eq("root_id", "fileid"));
 *     ```
 *     will not work, as the query builder doesn't know that the `mimetype` column belongs to the "filecache partition".
 *     Instead, you need to do
 *     ```
 *     $query->select("mount_point", "f.mimetype")
 *         ->from("mounts", "m")
 *         ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid"));
 *     ```
 *
 *  2. The "ON" condition for the join can only perform a comparison between both sides of the join once.
 *
 *     For example:
 *     ```
 *      $query->select("mount_point", "mimetype")
 *         ->from("mounts", "m")
 *         ->innerJoin("m", "filecache", "f", $query->expr()->andX($query->expr()->eq("m.root_id", "f.fileid"), $query->expr()->eq("m.storage_id", "f.storage")));
 *     ```
 *     will not work.
 *
 *  3. An "OR" expression in the "WHERE" cannot mention both sides of the join, this does not apply to "AND" expressions.
 *
 *      For example:
 *      ```
 *       $query->select("mount_point", "mimetype")
 *          ->from("mounts", "m")
 *          ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid")))
 *          ->where($query->expr()->orX(
 *              $query->expr()-eq("m.user_id", $query->createNamedParameter("test"))),
 *              $query->expr()-eq("f.name", $query->createNamedParameter("test"))),
 *          ));
 *      ```
 *      will not work, but.
 *      ```
 *       $query->select("mount_point", "mimetype")
 *          ->from("mounts", "m")
 *          ->innerJoin("m", "filecache", "f", $query->expr()->eq("m.root_id", "f.fileid")))
 *          ->where($query->expr()->andX(
 *              $query->expr()-eq("m.user_id", $query->createNamedParameter("test"))),
 *              $query->expr()-eq("f.name", $query->createNamedParameter("test"))),
 *          ));
 *      ```
 *      will.
 *
 *  4. Queries that join cross-partition cannot use position parameters, only named parameters are allowed
 *  5. The "ON" condition of a join cannot contain and "OR" expression.
 *  6. Right-joins are not allowed.
 *  7. Update, delete and insert statements aren't allowed to contain cross-partition joins.
 *  8. Queries that "GROUP BY" a column from the joined partition are not allowed.
 *  9. Any `join` call needs to be made before any `where` call.
 *  10. Queries that join cross-partition with an "INNER JOIN" or "LEFT JOIN" with a condition on the left side
 *      cannot use "LIMIT" or "OFFSET" in queries.
 *
 * The part of the query running on the sharded table has some additional limitations,
 * see the `InvalidShardedQueryException` documentation for more information.
 */
class InvalidPartitionedQueryException extends \Exception {

}
