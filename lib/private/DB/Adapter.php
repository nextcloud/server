<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use OC\DB\Exceptions\DbalException;

/**
 * This handles the way we use to write queries, into something that can be
 * handled by the database abstraction layer.
 */
class Adapter {
	/**
	 * @var \OC\DB\Connection $conn
	 */
	protected $conn;

	public function __construct($conn) {
		$this->conn = $conn;
	}

	/**
	 * @param string $table name
	 *
	 * @return int id of last insert statement
	 * @throws Exception
	 */
	public function lastInsertId($table) {
		return (int)$this->conn->realLastInsertId($table);
	}

	/**
	 * @param string $statement that needs to be changed so the db can handle it
	 * @return string changed statement
	 */
	public function fixupStatement($statement) {
		return $statement;
	}

	/**
	 * Create an exclusive read+write lock on a table
	 *
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function lockTable(string $tableName) {
		$this->conn->beginTransaction();
		$this->conn->executeUpdate('LOCK TABLE `' . $tableName . '` IN EXCLUSIVE MODE');
	}

	/**
	 * Release a previous acquired lock again
	 *
	 * @throws Exception
	 * @since 9.1.0
	 */
	public function unlockTable() {
		$this->conn->commit();
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function insertIgnoreConflict(string $table, array $values) : int {
		try {
			$builder = $this->conn->getQueryBuilder();
			$builder->insert($table);
			foreach ($values as $key => $value) {
				$builder->setValue($key, $builder->createNamedParameter($value));
			}
			return $builder->executeStatement();
		} catch (DbalException $e) {
			if ($e->getReason() === \OCP\DB\Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				return 0;
			}
			throw $e;
		}
	}
}
