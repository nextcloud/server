<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\DB;

class OracleConnection extends Connection {
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
}
