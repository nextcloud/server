<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OCP\DB\ISchemaWrapper;

class SchemaWrapper implements ISchemaWrapper {
	/** @var Connection */
	protected $connection;

	/** @var Schema */
	protected $schema;

	/** @var array */
	protected $tablesToDelete = [];

	public function __construct(Connection $connection) {
		$this->connection = $connection;
		$this->schema = $this->connection->createSchema();
	}

	public function getWrappedSchema() {
		return $this->schema;
	}

	public function performDropTableCalls() {
		foreach ($this->tablesToDelete as $tableName => $true) {
			$this->connection->dropTable($tableName);
			unset($this->tablesToDelete[$tableName]);
		}
	}

	/**
	 * Gets all table names
	 *
	 * @return array
	 */
	public function getTableNamesWithoutPrefix() {
		$tableNames = $this->schema->getTableNames();
		return array_map(function ($tableName) {
			if (str_starts_with($tableName, $this->connection->getPrefix())) {
				return substr($tableName, strlen($this->connection->getPrefix()));
			}

			return $tableName;
		}, $tableNames);
	}

	// Overwritten methods

	/**
	 * @return array
	 */
	public function getTableNames() {
		return $this->schema->getTableNames();
	}

	/**
	 * @param string $tableName
	 *
	 * @return \Doctrine\DBAL\Schema\Table
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 */
	public function getTable($tableName) {
		return $this->schema->getTable($this->connection->getPrefix() . $tableName);
	}

	/**
	 * Does this schema have a table with the given name?
	 *
	 * @param string $tableName
	 *
	 * @return boolean
	 */
	public function hasTable($tableName) {
		return $this->schema->hasTable($this->connection->getPrefix() . $tableName);
	}

	/**
	 * Creates a new table.
	 *
	 * @param string $tableName
	 * @return \Doctrine\DBAL\Schema\Table
	 */
	public function createTable($tableName) {
		unset($this->tablesToDelete[$tableName]);
		return $this->schema->createTable($this->connection->getPrefix() . $tableName);
	}

	/**
	 * Drops a table from the schema.
	 *
	 * @param string $tableName
	 * @return \Doctrine\DBAL\Schema\Schema
	 */
	public function dropTable($tableName) {
		$this->tablesToDelete[$tableName] = true;
		return $this->schema->dropTable($this->connection->getPrefix() . $tableName);
	}

	/**
	 * Gets all tables of this schema.
	 *
	 * @return \Doctrine\DBAL\Schema\Table[]
	 */
	public function getTables() {
		return $this->schema->getTables();
	}

	/**
	 * Gets the DatabasePlatform for the database.
	 *
	 * @return AbstractPlatform
	 *
	 * @throws Exception
	 */
	public function getDatabasePlatform() {
		return $this->connection->getDatabasePlatform();
	}
}
