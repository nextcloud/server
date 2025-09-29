<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

use Doctrine\DBAL\Schema\Schema;
use OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OCP\DB\Exception;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;

/**
 * Interface IDBConnection
 *
 * @since 6.0.0
 */
interface IDBConnection {
	/**
	 * @since 28.0.0
	 */
	public const PLATFORM_MYSQL = 'mysql';

	/**
	 * @since 28.0.0
	 */
	public const PLATFORM_ORACLE = 'oracle';

	/**
	 * @since 28.0.0
	 */
	public const PLATFORM_POSTGRES = 'postgres';

	/**
	 * @since 28.0.0
	 */
	public const PLATFORM_SQLITE = 'sqlite';

	/**
	 * @since 32.0.0
	 */
	public const PLATFORM_MARIADB = 'mariadb';

	/**
	 * Gets the QueryBuilder for the connection.
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 * @since 8.2.0
	 */
	public function getQueryBuilder();

	/**
	 * Used to abstract the Nextcloud database access away
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int|null $limit the maximum number of rows
	 * @param int|null $offset from which row we want to start
	 * @return IPreparedStatement The prepared statement.
	 * @since 6.0.0
	 * @throws Exception since 21.0.0
	 *
	 * @psalm-taint-sink sql $sql
	 */
	public function prepare($sql, $limit = null, $offset = null): IPreparedStatement;

	/**
	 * Executes an, optionally parameterized, SQL query.
	 *
	 * If the query is parameterized, a prepared statement is used.
	 * If an SQLLogger is configured, the execution is logged.
	 *
	 * @param string $sql The SQL query to execute.
	 * @param string[] $params The parameters to bind to the query, if any.
	 * @param array $types The types the previous parameters are in.
	 * @return IResult The executed statement.
	 * @since 8.0.0
	 * @throws Exception since 21.0.0
	 *
	 * @psalm-taint-sink sql $sql
	 */
	public function executeQuery(string $sql, array $params = [], $types = []): IResult;

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $sql The SQL query.
	 * @param array $params The query parameters.
	 * @param array $types The parameter types.
	 * @return int The number of affected rows.
	 * @since 8.0.0
	 * @throws Exception since 21.0.0
	 *
	 * @deprecated 21.0.0 use executeStatement
	 *
	 * @psalm-taint-sink sql $sql
	 */
	public function executeUpdate(string $sql, array $params = [], array $types = []): int;

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $sql The SQL query.
	 * @param array $params The query parameters.
	 * @param array $types The parameter types.
	 * @return int The number of affected rows.
	 * @since 21.0.0
	 * @throws Exception since 21.0.0
	 *
	 * @psalm-taint-sink sql $sql
	 */
	public function executeStatement($sql, array $params = [], array $types = []): int;

	/**
	 * Used to get the id of the just inserted element
	 * @param string $table the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 * @since 6.0.0
	 * @throws Exception since 21.0.0
	 * @deprecated 21.0.0 use \OCP\DB\QueryBuilder\IQueryBuilder::getLastInsertId
	 */
	public function lastInsertId(string $table): int;

	/**
	 * Insert a row if the matching row does not exists. To accomplish proper race condition avoidance
	 * it is needed that there is also a unique constraint on the values. Then this method will
	 * catch the exception and return 0.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *                            If this is null or an empty array, all keys of $input will be compared
	 *                            Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws Exception used to be the removed dbal exception, since 21.0.0 it's \OCP\DB\Exception
	 * @since 6.0.0 - parameter $compare was added in 8.1.0, return type changed from boolean in 8.1.0
	 * @deprecated 15.0.0 - use unique index and "try { $db->insert() } catch (\OCP\DB\Exception $e) { if ($e->getReason() === \OCP\DB\Exception::REASON_CONSTRAINT_VIOLATION) {} }" instead, because it is more reliable and does not have the risk for deadlocks - see https://github.com/nextcloud/server/pull/12371
	 */
	public function insertIfNotExist(string $table, array $input, ?array $compare = null);


	/**
	 *
	 * Insert a row if the row does not exist. Eventual conflicts during insert will be ignored.
	 *
	 * Implementation is not fully finished and should not be used!
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $values data that should be inserted into the table  (column name => value)
	 * @return int number of inserted rows
	 * @since 16.0.0
	 */
	public function insertIgnoreConflict(string $table, array $values) : int;

	/**
	 * Insert or update a row value
	 *
	 * @param string $table
	 * @param array $keys (column name => value)
	 * @param array $values (column name => value)
	 * @param array $updatePreconditionValues ensure values match preconditions (column name => value)
	 * @return int number of new rows
	 * @throws Exception used to be the removed dbal exception, since 21.0.0 it's \OCP\DB\Exception
	 * @throws PreConditionNotMetException
	 * @since 9.0.0
	 */
	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int;

	/**
	 * Create an exclusive read+write lock on a table
	 *
	 * Important Note: Due to the nature how locks work on different DBs, it is
	 * only possible to lock one table at a time. You should also NOT start a
	 * transaction while holding a lock.
	 *
	 * @param string $tableName
	 * @throws Exception since 21.0.0
	 * @since 9.1.0
	 */
	public function lockTable($tableName): void;

	/**
	 * Release a previous acquired lock again
	 *
	 * @throws Exception since 21.0.0
	 * @since 9.1.0
	 */
	public function unlockTable(): void;

	/**
	 * Start a transaction
	 * @since 6.0.0
	 * @throws Exception since 21.0.0
	 */
	public function beginTransaction(): void;

	/**
	 * Check if a transaction is active
	 *
	 * @return bool
	 * @since 8.2.0
	 */
	public function inTransaction(): bool;

	/**
	 * Commit the database changes done during a transaction that is in progress
	 * @since 6.0.0
	 * @throws Exception since 21.0.0
	 */
	public function commit(): void;

	/**
	 * Rollback the database changes done during a transaction that is in progress
	 * @since 6.0.0
	 * @throws Exception since 21.0.0
	 */
	public function rollBack(): void;

	/**
	 * Gets the error code and message as a string for logging
	 * @return string
	 * @since 6.0.0
	 * @deprecated 21.0.0 doesn't return anything meaningful
	 */
	public function getError(): string;

	/**
	 * Fetch the SQLSTATE associated with the last database operation.
	 *
	 * @return integer The last error code.
	 * @since 8.0.0
	 * @deprecated 21.0.0 doesn't return anything anymore
	 */
	public function errorCode();

	/**
	 * Fetch extended error information associated with the last database operation.
	 *
	 * @return array The last error information.
	 * @since 8.0.0
	 * @deprecated 21.0.0 doesn't return anything anymore
	 */
	public function errorInfo();

	/**
	 * Establishes the connection with the database.
	 *
	 * @return bool
	 * @throws Exception since 21.0.0
	 * @since 8.0.0
	 */
	public function connect(): bool;

	/**
	 * Close the database connection
	 * @since 8.0.0
	 */
	public function close(): void;

	/**
	 * Quotes a given input parameter.
	 *
	 * @param mixed $input Parameter to be quoted.
	 * @param int $type Type of the parameter.
	 * @return mixed The quoted parameter.
	 * @since 8.0.0
	 */
	public function quote($input, $type = IQueryBuilder::PARAM_STR);

	/**
	 * Gets the DatabasePlatform instance that provides all the metadata about
	 * the platform this driver connects to.
	 *
	 * @return \Doctrine\DBAL\Platforms\AbstractPlatform The database platform.
	 * @since 8.0.0
	 * @deprecated 30.0.0 Please use {@see self::getDatabaseProvider()} and compare to self::PLATFORM_* constants
	 */
	public function getDatabasePlatform();

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 * @throws Exception since 21.0.0
	 * @since 8.0.0
	 *
	 * @psalm-taint-sink sql $table
	 */
	public function dropTable(string $table): void;

	/**
	 * Truncate a table data if it exists
	 *
	 * Cascade is not supported on many platforms but would optionally cascade the truncate by
	 * following the foreign keys.
	 *
	 * @param string $table table name without the prefix
	 * @param bool $cascade whether to truncate cascading
	 * @throws Exception
	 * @since 32.0.0
	 *
	 * @psalm-taint-sink sql $table
	 */
	public function truncateTable(string $table, bool $cascade): void;

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 * @return bool
	 * @throws Exception since 21.0.0
	 * @since 8.0.0
	 */
	public function tableExists(string $table): bool;

	/**
	 * Escape a parameter to be used in a LIKE query
	 *
	 * @param string $param
	 * @return string
	 * @since 9.0.0
	 */
	public function escapeLikeParameter(string $param): string;

	/**
	 * Check whether or not the current database support 4byte wide unicode
	 *
	 * @return bool
	 * @since 11.0.0
	 */
	public function supports4ByteText(): bool;

	/**
	 * Create the schema of the connected database
	 *
	 * @return Schema
	 * @throws Exception since 21.0.0
	 * @since 13.0.0
	 */
	public function createSchema(): Schema;

	/**
	 * Migrate the database to the given schema
	 *
	 * @param Schema $toSchema
	 * @throws Exception since 21.0.0
	 * @since 13.0.0
	 */
	public function migrateToSchema(Schema $toSchema): void;

	/**
	 * Returns the database provider name
	 *
	 * @link https://github.com/nextcloud/server/issues/30877
	 *
	 * @param bool $strict differentiate between database flavors, e.g. MySQL vs MariaDB
	 * @return self::PLATFORM_MYSQL|self::PLATFORM_ORACLE|self::PLATFORM_POSTGRES|self::PLATFORM_SQLITE|self::PLATFORM_MARIADB
	 * @since 32.0.0 Optional parameter $strict was added
	 * @since 28.0.0
	 */
	public function getDatabaseProvider(bool $strict = false): string;

	/**
	 * Get the shard definition by name, if configured
	 *
	 * @param string $name
	 * @return ShardDefinition|null
	 * @since 30.0.0
	 */
	public function getShardDefinition(string $name): ?ShardDefinition;

	/**
	 * Get a helper class for implementing cross-shard moves
	 *
	 * @return CrossShardMoveHelper
	 * @since 30.0.0
	 */
	public function getCrossShardMoveHelper(): CrossShardMoveHelper;
}
