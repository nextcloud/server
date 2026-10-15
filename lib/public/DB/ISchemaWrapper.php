<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use OCP\AppFramework\Attribute\Consumable;
use OCP\DB\Schema\ITable;

/**
 * This interface allows to get information about the database schema.
 * This is particularly helpful for database migration scripts.
 *
 * This interface must not be implemented in your application but
 * instead can be obtained in your migration scripts with the
 * `$schemaClosure` Closure.
 *
 * @since 13.0.0
 */
#[Consumable(since: '13.0.0')]
interface ISchemaWrapper {
	/**
	 * @throws Schema\SchemaException
	 * @since 13.0.0
	 */
	public function getTable(string $tableName): ITable;

	/**
	 * Does this schema have a table with the given name?
	 *
	 * @param string $tableName Prefix is automatically prepended
	 *
	 * @since 13.0.0
	 */
	public function hasTable(string $tableName): bool;

	/**
	 * Creates a new table.
	 *
	 * @param string $tableName Prefix is automatically prepended
	 * @since 13.0.0
	 */
	public function createTable(string $tableName): ITable;

	/**
	 * Drops a table from the schema.
	 *
	 * @param string $tableName Prefix is automatically prepended
	 * @since 13.0.0
	 */
	public function dropTable(string $tableName): self;

	/**
	 * Gets all tables of this schema.
	 *
	 * @return list<ITable>
	 * @since 13.0.0
	 */
	public function getTables(): array;

	/**
	 * Gets all table names, prefixed with table prefix
	 *
	 * @return list<string>
	 * @since 13.0.0
	 */
	public function getTableNames(): array;

	/**
	 * Gets all table names
	 *
	 * @return list<string>
	 * @since 13.0.0
	 */
	public function getTableNamesWithoutPrefix(): array;

	/**
	 * Gets the DatabasePlatform for the database.
	 *
	 * @return AbstractPlatform
	 *
	 * @throws Exception
	 * @since 23.0.0
	 */
	public function getDatabasePlatform(): AbstractPlatform;

	/**
	 * Drop autoincrement from an existing table of the database.
	 *
	 * @since 33.0.0
	 */
	public function dropAutoincrementColumn(string $table, string $column): void;
}
