<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException as DBALSchemaException;
use Doctrine\DBAL\Schema\Table as DBALTable;
use OC\DB\Schema\Table;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Schema\ITable;
use OCP\DB\Schema\SchemaException;
use OCP\Server;
use Psr\Log\LoggerInterface;

class SchemaWrapper implements ISchemaWrapper {
	protected Schema $schema;

	/** @var array<string, true> */
	protected array $tablesToDelete = [];

	public function __construct(
		protected readonly Connection $connection,
		?Schema $schema = null,
	) {
		if ($schema !== null) {
			$this->schema = $schema;
		} else {
			$this->schema = $this->connection->createSchema();
		}
	}

	public function getWrappedSchema(): Schema {
		return $this->schema;
	}

	public function performDropTableCalls(): void {
		foreach ($this->tablesToDelete as $tableName => $true) {
			$this->connection->dropTable($tableName);
			foreach ($this->connection->getShardConnections() as $shardConnection) {
				$shardConnection->dropTable($tableName);
			}
			unset($this->tablesToDelete[$tableName]);
		}
	}

	#[\Override]
	public function getTableNamesWithoutPrefix(): array {
		$tableNames = $this->getTableNames();
		return array_map(function ($tableName) {
			if (str_starts_with($tableName, $this->connection->getPrefix())) {
				return substr($tableName, strlen($this->connection->getPrefix()));
			}

			return $tableName;
		}, $tableNames);
	}

	// Overwritten methods

	#[\Override]
	public function getTableNames(): array {
		return array_values(array_map(fn (DBALTable $table): string => $table->getName(), $this->schema->getTables()));
	}

	#[\Override]
	public function getTable(string $tableName): ITable {
		try {
			return new Table($this->schema->getTable($this->connection->getPrefix() . $tableName));
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Does this schema have a table with the given name?
	 */
	#[\Override]
	public function hasTable(string $tableName): bool {
		return $this->schema->hasTable($this->connection->getPrefix() . $tableName);
	}

	#[\Override]
	public function createTable(string $tableName): ITable {
		unset($this->tablesToDelete[$tableName]);
		try {
			return new Table($this->schema->createTable($this->connection->getPrefix() . $tableName));
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}

	#[\Override]
	public function dropTable(string $tableName): self {
		$this->tablesToDelete[$tableName] = true;
		$this->schema->dropTable($this->connection->getPrefix() . $tableName);
		return $this;
	}

	#[\Override]
	public function getTables(): array {
		return array_values(array_map(fn (DBALTable $table): ITable => new Table($table), $this->schema->getTables()));
	}

	#[\Override]
	public function getDatabasePlatform(): AbstractPlatform {
		return $this->connection->getDatabasePlatform();
	}

	#[\Override]
	public function dropAutoincrementColumn(string $table, string $column): void {
		$tableObj = $this->schema->getTable($this->connection->getPrefix() . $table);
		$tableObj->modifyColumn($column, ['autoincrement' => false]);
		$platform = $this->getDatabasePlatform();
		if ($platform instanceof OraclePlatform) {
			try {
				$this->connection->executeStatement('DROP TRIGGER "' . $this->connection->getPrefix() . $table . '_AI_PK"');
				$this->connection->executeStatement('DROP SEQUENCE "' . $this->connection->getPrefix() . $table . '_SEQ"');
			} catch (Exception $e) {
				Server::get(LoggerInterface::class)->error($e->getMessage(), ['exception' => $e]);
			}
		}
	}
}
