<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;

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
interface ISchemaWrapper {
	/**
	 * @param string $tableName
	 *
	 * @return \Doctrine\DBAL\Schema\Table
	 * @throws \Doctrine\DBAL\Schema\SchemaException
	 * @since 13.0.0
	 */
	public function getTable($tableName);

	/**
	 * Does this schema have a table with the given name?
	 *
	 * @param string $tableName Prefix is automatically prepended
	 *
	 * @return boolean
	 * @since 13.0.0
	 */
	public function hasTable($tableName);

	/**
	 * Creates a new table.
	 *
	 * @param string $tableName Prefix is automatically prepended
	 * @return \Doctrine\DBAL\Schema\Table
	 * @since 13.0.0
	 */
	public function createTable($tableName);

	/**
	 * Drops a table from the schema.
	 *
	 * @param string $tableName Prefix is automatically prepended
	 * @return \Doctrine\DBAL\Schema\Schema
	 * @since 13.0.0
	 */
	public function dropTable($tableName);

	/**
	 * Gets all tables of this schema.
	 *
	 * @return \Doctrine\DBAL\Schema\Table[]
	 * @since 13.0.0
	 */
	public function getTables();

	/**
	 * Gets all table names, prefixed with table prefix
	 *
	 * @return array
	 * @since 13.0.0
	 */
	public function getTableNames();

	/**
	 * Gets all table names
	 *
	 * @return array
	 * @since 13.0.0
	 */
	public function getTableNamesWithoutPrefix();

	/**
	 * Gets the DatabasePlatform for the database.
	 *
	 * @return AbstractPlatform
	 *
	 * @throws Exception
	 * @since 23.0.0
	 */
	public function getDatabasePlatform();
}
