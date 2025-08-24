<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class AdapterSqlite extends Adapter {
	/**
	 * @param string $tableName
	 */
	public function lockTable($tableName) {
		$this->conn->executeUpdate('BEGIN EXCLUSIVE TRANSACTION');
	}

	public function unlockTable() {
		$this->conn->executeUpdate('COMMIT TRANSACTION');
	}

	public function fixupStatement($statement) {
		$statement = preg_replace('/`(\w+)` ILIKE \?/', 'LOWER($1) LIKE LOWER(?)', $statement);
		$statement = str_replace('`', '"', $statement);
		$statement = str_ireplace('NOW()', 'datetime(\'now\')', $statement);
		$statement = str_ireplace('GREATEST(', 'MAX(', $statement);
		$statement = str_ireplace('UNIX_TIMESTAMP()', 'strftime(\'%s\',\'now\')', $statement);
		return $statement;
	}

	/**
	 * Insert a row if the matching row does not exists. To accomplish proper race condition avoidance
	 * it is needed that there is also a unique constraint on the values. Then this method will
	 * catch the exception and return 0.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *                            If this is null or an empty array, all keys of $input will be compared
	 *                            Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\Exception
	 * @deprecated 15.0.0 - use unique index and "try { $db->insert() } catch (UniqueConstraintViolationException $e) {}" instead, because it is more reliable and does not have the risk for deadlocks - see https://github.com/nextcloud/server/pull/12371
	 */
	public function insertIfNotExist($table, $input, ?array $compare = null) {
		if (empty($compare)) {
			$compare = array_keys($input);
		}
		$fieldList = '`' . implode('`,`', array_keys($input)) . '`';
		$query = "INSERT INTO `$table` ($fieldList) SELECT "
			. str_repeat('?,', count($input) - 1) . '? '
			. " WHERE NOT EXISTS (SELECT 1 FROM `$table` WHERE ";

		$inserts = array_values($input);
		foreach ($compare as $key) {
			$query .= '`' . $key . '`';
			if (is_null($input[$key])) {
				$query .= ' IS NULL AND ';
			} else {
				$inserts[] = $input[$key];
				$query .= ' = ? AND ';
			}
		}
		$query = substr($query, 0, -5);
		$query .= ')';

		try {
			return $this->conn->executeUpdate($query, $inserts);
		} catch (UniqueConstraintViolationException $e) {
			// if this is thrown then a concurrent insert happened between the insert and the sub-select in the insert, that should have avoided it
			// it's fine to ignore this then
			//
			// more discussions about this can be found at https://github.com/nextcloud/server/pull/12315
			return 0;
		}
	}

	public function insertIgnoreConflict(string $table, array $values): int {
		$builder = $this->conn->getQueryBuilder();
		$builder->insert($table);
		$updates = [];
		foreach ($values as $key => $value) {
			$builder->setValue($key, $builder->createNamedParameter($value));
		}

		return $this->conn->executeStatement(
			$builder->getSQL() . ' ON CONFLICT DO NOTHING',
			$builder->getParameters(),
			$builder->getParameterTypes()
		);
	}
}
