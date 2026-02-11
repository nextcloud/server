<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use OCP\AppFramework\Attribute\Consumable;
use PDO;
use Traversable;

/**
 * This interface represents the result of a database query.
 *
 * Usage:
 *
 * ```php
 * $qb = $this->db->getQueryBuilder();
 * $qb->select(...);
 * $result = $query->executeQuery();
 *
 * foreach ($result->iterateAssociative() as $row) {
 *     $id = $row['id'];
 * }
 * ```
 *
 * @since 21.0.0
 */
#[Consumable(since: '21.0.0')]
interface IResult {
	/**
	 * @return true
	 *
	 * @since 21.0.0
	 */
	public function closeCursor(): bool;

	/**
	 * @param PDO::FETCH_* $fetchMode
	 *
	 * @return ($fetchMode is PDO::FETCH_ASSOC ? array<string, mixed> : ($fetchMode is PDO::FETCH_NUM ? list<mixed> : mixed))|false
	 *
	 * @since 21.0.0
	 * @note Since 33.0.0, prefer using fetchAssociative/fetchNumeric/fetchOne or iterateAssociate/iterateNumeric instead.
	 */
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC);

	/**
	 * Returns the next row of the result as an associative array or FALSE if there are no more rows.
	 *
	 * @return array<string, mixed>|false
	 *
	 * @since 33.0.0
	 */
	public function fetchAssociative(): array|false;

	/**
	 * Returns the next row of the result as a numeric array or FALSE if there are no more rows.
	 *
	 * @return list<mixed>|false
	 *
	 * @since 33.0.0
	 */
	public function fetchNumeric(): array|false;

	/**
	 * Returns the first value of the next row of the result or FALSE if there are no more rows.
	 *
	 * @return false|mixed
	 *
	 * @since 21.0.0
	 */
	public function fetchOne();

	/**
	 * @param PDO::FETCH_* $fetchMode
	 *
	 * @return list<($fetchMode is PDO::FETCH_ASSOC ? array<string, mixed> : ($fetchMode is PDO::FETCH_NUM ? list<mixed> : mixed))>
	 *
	 * @since 21.0.0
	 * @note Since 33.0.0, prefer using fetchAllAssociative/fetchAllNumeric/fetchFirstColumn or iterateAssociate/iterateNumeric instead.
	 */
	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array;

	/**
	 * Returns an array containing all the result rows represented as associative arrays.
	 *
	 * @return list<array<string,mixed>>
	 * @since 33.0.0
	 */
	public function fetchAllAssociative(): array;

	/**
	 * Returns an array containing all the result rows represented as numeric arrays.
	 *
	 * @return list<list<mixed>>
	 * @since 33.0.0
	 */
	public function fetchAllNumeric(): array;

	/**
	 * Returns the value of the first column of all rows.
	 *
	 * @return list<mixed>
	 * @since 33.0.0
	 */
	public function fetchFirstColumn(): array;

	/**
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetchOne
	 */
	public function fetchColumn();

	/**
	 * @return int
	 *
	 * @since 21.0.0
	 */
	public function rowCount(): int;

	/**
	 * Returns an iterator over rows represented as numeric arrays.
	 *
	 * @return Traversable<list<mixed>>
	 *
	 * @since 33.0.0
	 */
	public function iterateNumeric(): Traversable;

	/**
	 * Returns an iterator over rows represented as associative arrays.
	 *
	 * @return Traversable<array<string,mixed>>
	 *
	 * @since 33.0.0
	 */
	public function iterateAssociative(): Traversable;
}
