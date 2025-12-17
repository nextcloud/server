<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

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
