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
	 */
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC);

	/**
	 * @param int $fetchMode (one of PDO::FETCH_ASSOC, PDO::FETCH_NUM or PDO::FETCH_COLUMN (2, 3 or 7)
	 *
	 * @return mixed[]
	 *
	 * @since 21.0.0
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
	 * Returns the first value of the next row of the result or FALSE if there are no more rows.
	 *
	 * @return false|mixed
	 *
	 * @since 21.0.0
	 */
	public function fetchOne();

	/**
	 * @return int
	 *
	 * @since 21.0.0
	 */
	public function rowCount(): int;
}
