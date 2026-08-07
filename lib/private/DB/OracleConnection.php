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
	private function quoteKeys(array $data): array {
		$return = [];
		foreach ($data as $key => $value) {
			$return[$this->quoteIdentifier($key)] = $value;
		}
		return $return;
	}

	#[\Override]
	public function truncateTable(string $table, bool $cascade) {
		if ($table[0] !== $this->getDatabasePlatform()->getIdentifierQuoteCharacter()) {
			$table = $this->quoteIdentifier($table);
		}
		return parent::truncateTable($table, $cascade);
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function insert(string $table, array $data, array $types = []): int|string {
		$table = $this->quoteIdentifier($table);
		$data = $this->quoteKeys($data);
		return parent::insert($table, $data, $types);
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function update(string $table, array $data, array $criteria = [], array $types = []): int|string {
		$table = $this->quoteIdentifier($table);
		$data = $this->quoteKeys($data);
		$criteria = $this->quoteKeys($criteria);
		return parent::update($table, $data, $criteria, $types);
	}

	/**
	 * {@inheritDoc}
	 */
	#[\Override]
	public function delete(string $table, array $criteria = [], array $types = []): int|string {
		$table = $this->quoteIdentifier($table);
		$criteria = $this->quoteKeys($criteria);
		return parent::delete($table, $criteria);
	}

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 */
	#[\Override]
	public function dropTable($table): void {
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
	#[\Override]
	public function tableExists($table): bool {
		$table = $this->tablePrefix . trim($table);
		$table = $this->quoteIdentifier($table);
		$schema = $this->createSchemaManager();
		return $schema->tablesExist([$table]);
	}
}
