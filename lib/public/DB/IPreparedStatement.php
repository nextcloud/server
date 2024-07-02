<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use Doctrine\DBAL\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use PDO;

/**
 * This interface allows you to prepare a database query.
 *
 * This interface must not be implemented in your application but
 * instead obtained from IDBConnection::prepare.
 *
 * ```php
 * $prepare = $this->db->prepare($query->getSql());
 * ```
 *
 * @since 21.0.0
 */
interface IPreparedStatement {
	/**
	 * @return true
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::closeCursor on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function closeCursor(): bool;

	/**
	 * @param int $fetchMode
	 *
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetch on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function fetch(int $fetchMode = PDO::FETCH_ASSOC);

	/**
	 * @param int $fetchMode
	 *
	 * @return mixed[]
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetchAll on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC);

	/**
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetchColumn on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function fetchColumn();

	/**
	 * @return mixed
	 *
	 * @since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\IResult::fetchOne on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function fetchOne();

	/**
	 * @param int|string $param
	 * @param mixed $value
	 * @param int $type
	 *
	 * @return bool
	 *
	 * @throws Exception
	 *
	 * @since 21.0.0
	 */
	public function bindValue($param, $value, $type = IQueryBuilder::PARAM_STR): bool;

	/**
	 * @param int|string $param
	 * @param mixed $variable
	 * @param int $type
	 * @param int|null $length
	 *
	 * @return bool
	 *
	 * @throws Exception
	 *
	 * @since 21.0.0
	 * @deprecated 30.0.0 Use {@see self::bindValue()} instead
	 */
	public function bindParam($param, &$variable, $type = IQueryBuilder::PARAM_STR, $length = null): bool;

	/**
	 * @param mixed[]|null $params
	 *
	 * @return IResult
	 *
	 * @since 21.0.0
	 * @throws Exception
	 */
	public function execute($params = null): IResult;

	/**
	 * @return int
	 *
	 * @since 21.0.0
	 *
	 * @throws Exception
	 * @deprecated 21.0.0 use \OCP\DB\IResult::rowCount on the \OCP\DB\IResult returned by \OCP\IDBConnection::prepare
	 */
	public function rowCount(): int;
}
