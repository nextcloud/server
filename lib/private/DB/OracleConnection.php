<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

class OracleConnection extends Connection {
	/** @var array<string, int> */
	protected array $lastInsertId = [];

	/**
	 * Quote the keys of the array
	 * @param array<string, string> $data
	 * @return array<string, string>
	 */
	private function quoteKeys(array $data) {
		$return = [];
		$c = $this->getDatabasePlatform()->getIdentifierQuoteCharacter();
		foreach ($data as $key => $value) {
			if ($key[0] !== $c) {
				$return[$this->quoteIdentifier($key)] = $value;
			} else {
				$return[$key] = $value;
			}
		}
		return $return;
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert($table, array $data, array $types = []) {
		if ($table[0] !== $this->getDatabasePlatform()->getIdentifierQuoteCharacter()) {
			$table = $this->quoteIdentifier($table);
		}
		$data = $this->quoteKeys($data);
		return parent::insert($table, $data, $types);
	}

	/**
	 * {@inheritDoc}
	 */
	public function update($table, array $data, array $criteria, array $types = []) {
		if ($table[0] !== $this->getDatabasePlatform()->getIdentifierQuoteCharacter()) {
			$table = $this->quoteIdentifier($table);
		}
		$data = $this->quoteKeys($data);
		$criteria = $this->quoteKeys($criteria);
		return parent::update($table, $data, $criteria, $types);
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($table, array $criteria, array $types = []) {
		if ($table[0] !== $this->getDatabasePlatform()->getIdentifierQuoteCharacter()) {
			$table = $this->quoteIdentifier($table);
		}
		$criteria = $this->quoteKeys($criteria);
		return parent::delete($table, $criteria);
	}

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 */
	public function dropTable($table) {
		$table = $this->tablePrefix . trim($table);
		$table = $this->quoteIdentifier($table);
		$schema = $this->createSchemaManager();
		if ($schema->tablesExist([$table])) {
			$schema->dropTable($table);
		}
	}

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 * @return bool
	 */
	public function tableExists($table) {
		$table = $this->tablePrefix . trim($table);
		$table = $this->quoteIdentifier($table);
		$schema = $this->createSchemaManager();
		return $schema->tablesExist([$table]);
	}

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $sql The SQL query.
	 * @param array $params The query parameters.
	 * @param array $types The parameter types.
	 *
	 * @return int The number of affected rows, if the result is bigger than PHP_INT_MAX, PHP_INT_MAX is returned
	 *
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function executeStatement($sql, array $params = [], array $types = []): int {
		$returned = parent::executeStatement($sql, $params, $types);

		var_dump($sql);
		if (preg_match('/RETURNING/', $sql, $matches)) {
			var_dump($returned);
			$this->lastInsertId[$matches[1]] = $returned;
			var_dump($this->lastInsertId);
			$returned = 1;
		}

		return $returned;
	}

	public function lastInsertId($name = null): int {
		if ($name) {
			$name = $this->replaceTablePrefix($name);
		}
		return $this->lastInsertId[$name];
	}
}
