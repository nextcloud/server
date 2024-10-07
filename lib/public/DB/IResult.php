<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use PDO;

/**
 * This interface represents the result of a database query.
 *
 * Usage:
 *
 * ```php
 * $qb = $this->db->getQueryBuilder();
 * $qb->select(...);
 * $result = $query->executeQuery();
 * ```
 *
 * This interface must not be implemented in your application.
 *
 * @since 21.0.0
 */
interface IResult {
	/**
	 * @return true
	 *
	 * @since 21.0.0
	 */
	public function closeCursor(): bool;

	/**
	 * @param int $fetchMode
	 *
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 28.0.0 use fetchAssociative instead of fetch(), fetchNumeric instead of fetch(\PDO::FETCH_NUM) and fetchOne instead of fetch(\PDO::FETCH_COLUMN)
	 */
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC);

	/**
	 * Returns the next row of the result as an associative array or FALSE if there are no more rows.
	 *
	 * @return array<string,mixed>|false
	 * @throws Exception
	 *
	 * @since 28.0.0
	 */
	public function fetchAssociative(): array|false;

	/**
	 * Returns an array containing all of the result rows represented as associative arrays
	 *
	 * @return list<array<string,mixed>>
	 * @throws Exception
	 *
	 * @since 28.0.0
	 */
	public function fetchAllAssociative(): array;

	/**
	 * @param int $fetchMode (one of PDO::FETCH_ASSOC, PDO::FETCH_NUM or PDO::FETCH_COLUMN (2, 3 or 7)
	 *
	 * @return mixed[]
	 *
	 * @since 21.0.0
	 * @deprecated 28.0.0 use fetchAllAssociative instead of fetchAll(), fetchAllNumeric instead of fetchAll(FETCH_NUM) and fetchOne instead of fetchAll(FETCH_COLUMN)
	 */
	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array;

	/**
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetchOne
	 */
	public function fetchColumn();

	/**
	 * Returns the next row of the result as a numeric array or FALSE if there are no more rows
	 *
	 * @return list<mixed>|false
	 * @throws Exception
	 *
	 * @since 28.0.0
	 */
	public function fetchNumeric(): array|false;

	/**
	 * Returns an array containing all of the result rows represented as numeric arrays
	 *
	 * @return list<list<mixed>>
	 * @throws Exception
	 *
	 * @since 28.0.0
	 */
	public function fetchAllNumeric(): array;

	/**
	 * Returns the first value of the next row of the result or FALSE if there are no more rows.
	 *
	 * @return false|mixed
	 *
	 * @since 21.0.0
	 */
	public function fetchOne();

	/**
	 * Returns an array containing the values of the first column of the result
	 *
	 * @return list<mixed>
	 * @throws Exception
	 *
	 * @since 28.0.0
	 */
	public function fetchFirstColumn(): array;

	/**
	 * @return int
	 *
	 * @since 21.0.0
	 */
	public function rowCount(): int;
}
