<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

class AdapterMySQL extends Adapter {
	/** @var string */
	protected $collation;

	/**
	 * @param string $tableName
	 */
	public function lockTable($tableName) {
		$this->conn->executeUpdate('LOCK TABLES `' . $tableName . '` WRITE');
	}

	public function unlockTable() {
		$this->conn->executeUpdate('UNLOCK TABLES');
	}

	public function fixupStatement($statement) {
		$statement = str_replace(' ILIKE ', ' COLLATE ' . $this->getCollation() . ' LIKE ', $statement);
		return $statement;
	}

	protected function getCollation(): string {
		if (!$this->collation) {
			$params = $this->conn->getParams();
			$this->collation = $params['collation'] ?? (($params['charset'] ?? 'utf8') . '_general_ci');
		}

		return $this->collation;
	}

	public function insertIgnoreConflict(string $table, array $values): int {
		$builder = $this->conn->getQueryBuilder();
		$builder->insert($table);
		$updates = [];
		foreach ($values as $key => $value) {
			$builder->setValue($key, $builder->createNamedParameter($value));
		}

		/*
		 * We can't use ON DUPLICATE KEY UPDATE here because Nextcloud use the CLIENT_FOUND_ROWS flag
		 * With this flag the MySQL returns the number of selected rows
		 * instead of the number of affected/modified rows
		 * It's impossible to change this behaviour at runtime or for a single query
		 * Then, the result is 1 if a row is inserted and also 1 if a row is updated with same or different values
		 *
		 * With INSERT IGNORE, the result is 1 when a row is inserted, 0 otherwise
		 *
		 * Risk: it can also ignore other errors like type mismatch or truncated data…
		 */
		$res = $this->conn->executeStatement(
			preg_replace('/^INSERT/i', 'INSERT IGNORE', $builder->getSQL()),
			$builder->getParameters(),
			$builder->getParameterTypes()
		);

		return $res;
	}
}
