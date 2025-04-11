<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\QueryBuilder\Partitioned;

/**
 * Information about a database partition, containing the tables in the partition and any active alias
 */
class PartitionSplit {
	/** @var array<string, string> */
	public array $aliases = [];

	/**
	 * @param string[] $tables
	 */
	public function __construct(
		public string $name,
		public array $tables,
	) {
	}

	public function addAlias(string $table, string $alias): void {
		if ($this->containsTable($table)) {
			$this->aliases[$alias] = $table;
		}
	}

	public function addTable(string $table): void {
		if (!$this->containsTable($table)) {
			$this->tables[] = $table;
		}
	}

	public function containsTable(string $table): bool {
		return in_array($table, $this->tables);
	}

	public function containsAlias(string $alias): bool {
		return array_key_exists($alias, $this->aliases);
	}

	private function getTablesAndAliases(): array {
		return array_keys($this->aliases) + $this->tables;
	}

	/**
	 * Check if a query predicate mentions a table or alias from this partition
	 *
	 * @param string $predicate
	 * @return bool
	 */
	public function checkPredicateForTable(string $predicate): bool {
		foreach ($this->getTablesAndAliases() as $name) {
			if (str_contains($predicate, "`$name`.`")) {
				return true;
			}
		}
		return false;
	}

	public function isColumnInPartition(string $column): bool {
		foreach ($this->getTablesAndAliases() as $name) {
			if (str_starts_with($column, "$name.")) {
				return true;
			}
		}
		return false;
	}
}
