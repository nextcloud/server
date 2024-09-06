<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Schema\Schema;
use OCP\DB\ISchemaWrapper;

class SchemaWrapper implements ISchemaWrapper {
	/** @var Connection */
	protected $connection;

	/** @var Schema */
	protected $schema;

	/** @var array */
	protected $tablesToDelete = [];

	public function __construct(Connection $connection, ?Schema $schema = null) {
		$this->connection = $connection;
		if ($schema) {
			$this->schema = $schema;
		} else {
			$this->schema = $this->connection->createSchema();
		}
	}

	public function getWrappedSchema() {
		return $this->schema;
	}

	public function performDropTableCalls() {
		foreach ($this->tablesToDelete as $tableName => $true) {
			$this->connection->dropTable($tableName);
			foreach ($this->connection->getShardConnections() as $shardConnection) {
				$shardConnection->dropTable($tableName);
			}
			unset($this->tablesToDelete[$tableName]);
		}
	}

	public function getTableNamesWithoutPrefix() {
		$tableNames = $this->schema->getTableNames();
		return array_map(function ($tableName) {
			if (str_starts_with($tableName, $this->connection->getPrefix())) {
				return substr($tableName, strlen($this->connection->getPrefix()));
			}

			return $tableName;
		}, $tableNames);
	}

	public function getTableNames() {
		return $this->schema->getTableNames();
	}

	public function getTable($tableName) {
		return $this->schema->getTable($this->connection->getPrefix() . $tableName);
	}

	public function hasTable($tableName) {
		return $this->schema->hasTable($this->connection->getPrefix() . $tableName);
	}

	public function createTable($tableName) {
		unset($this->tablesToDelete[$tableName]);
		return $this->schema->createTable($this->connection->getPrefix() . $tableName);
	}

	public function dropTable($tableName) {
		$this->tablesToDelete[$tableName] = true;
		return $this->schema->dropTable($this->connection->getPrefix() . $tableName);
	}

	public function getTables() {
		return $this->schema->getTables();
	}

	public function getDatabasePlatform() {
		return $this->connection->getDatabasePlatform();
	}
}
