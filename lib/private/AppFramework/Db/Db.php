<?php
/**
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\AppFramework\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDb;
use OCP\IDBConnection;
use OCP\PreConditionNotMetException;

/**
 * @deprecated use IDBConnection directly, will be removed in ownCloud 10
 * Small Facade for being able to inject the database connection for tests
 */
class Db implements IDb {
	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * Gets the ExpressionBuilder for the connection.
	 *
	 * @return \OCP\DB\QueryBuilder\IQueryBuilder
	 */
	public function getQueryBuilder() {
		return $this->connection->getQueryBuilder();
	}

	/**
	 * Used to abstract the ownCloud database access away
	 *
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @deprecated use prepare instead, will be removed in ownCloud 10
	 * @return \OC_DB_StatementWrapper prepared SQL query
	 */
	public function prepareQuery($sql, $limit = null, $offset = null) {
		$isManipulation = \OC_DB::isManipulation($sql);
		$statement = $this->connection->prepare($sql, $limit, $offset);
		return new \OC_DB_StatementWrapper($statement, $isManipulation);
	}


	/**
	 * Used to get the id of the just inserted element
	 *
	 * @deprecated use lastInsertId instead, will be removed in ownCloud 10
	 * @param string $tableName the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 */
	public function getInsertId($tableName) {
		return $this->connection->lastInsertId($tableName);
	}

	/**
	 * Used to abstract the ownCloud database access away
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @return \Doctrine\DBAL\Driver\Statement The prepared statement.
	 */
	public function prepare($sql, $limit=null, $offset=null) {
		return $this->connection->prepare($sql, $limit, $offset);
	}

	/**
	 * Executes an, optionally parameterized, SQL query.
	 *
	 * If the query is parameterized, a prepared statement is used.
	 * If an SQLLogger is configured, the execution is logged.
	 *
	 * @param string $query The SQL query to execute.
	 * @param string[] $params The parameters to bind to the query, if any.
	 * @param array $types The types the previous parameters are in.
	 * @return \Doctrine\DBAL\Driver\Statement The executed statement.
	 */
	public function executeQuery($query, array $params = array(), $types = array()) {
		return $this->connection->executeQuery($query, $params, $types);
	}

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string $query The SQL query.
	 * @param array $params The query parameters.
	 * @param array $types The parameter types.
	 * @return integer The number of affected rows.
	 */
	public function executeUpdate($query, array $params = array(), array $types = array()) {
		return $this->connection->executeUpdate($query, $params, $types);
	}

	/**
	 * Used to get the id of the just inserted element
	 * @param string $table the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 */
	public function lastInsertId($table = null) {
		return $this->connection->lastInsertId($table);
	}

	/**
	 * Insert a row if the matching row does not exists.
	 *
	 * @param string $table The table name (will replace *PREFIX* with the actual prefix)
	 * @param array $input data that should be inserted into the table  (column name => value)
	 * @param array|null $compare List of values that should be checked for "if not exists"
	 *				If this is null or an empty array, all keys of $input will be compared
	 *				Please note: text fields (clob) must not be used in the compare array
	 * @return int number of inserted rows
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function insertIfNotExist($table, $input, array $compare = null) {
		return $this->connection->insertIfNotExist($table, $input, $compare);
	}

	/**
	 * Insert or update a row value
	 *
	 * @param string $table
	 * @param array $keys (column name => value)
	 * @param array $values (column name => value)
	 * @param array $updatePreconditionValues ensure values match preconditions (column name => value)
	 * @return int number of new rows
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws PreConditionNotMetException
	 */
	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []) {
		return $this->connection->setValues($table, $keys, $values, $updatePreconditionValues);
	}

	/**
	 * @inheritdoc
	 */
	public function lockTable($tableName) {
		$this->connection->lockTable($tableName);
	}

	/**
	 * @inheritdoc
	 */
	public function unlockTable() {
		$this->connection->unlockTable();
	}

	/**
	 * Start a transaction
	 */
	public function beginTransaction() {
		$this->connection->beginTransaction();
	}

	/**
	 * Check if a transaction is active
	 *
	 * @return bool
	 */
	public function inTransaction() {
		return $this->connection->inTransaction();
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 */
	public function commit() {
		$this->connection->commit();
	}

	/**
	 * Rollback the database changes done during a transaction that is in progress
	 */
	public function rollBack() {
		$this->connection->rollBack();
	}

	/**
	 * Gets the error code and message as a string for logging
	 * @return string
	 */
	public function getError() {
		return $this->connection->getError();
	}

	/**
	 * Fetch the SQLSTATE associated with the last database operation.
	 *
	 * @return integer The last error code.
	 */
	public function errorCode() {
		return $this->connection->errorCode();
	}

	/**
	 * Fetch extended error information associated with the last database operation.
	 *
	 * @return array The last error information.
	 */
	public function errorInfo() {
		return $this->connection->errorInfo();
	}

	/**
	 * Establishes the connection with the database.
	 *
	 * @return bool
	 */
	public function connect() {
		return $this->connection->connect();
	}

	/**
	 * Close the database connection
	 */
	public function close() {
		$this->connection->close();
	}

	/**
	 * Quotes a given input parameter.
	 *
	 * @param mixed $input Parameter to be quoted.
	 * @param int $type Type of the parameter.
	 * @return string The quoted parameter.
	 */
	public function quote($input, $type = IQueryBuilder::PARAM_STR) {
		return $this->connection->quote($input, $type);
	}

	/**
	 * Gets the DatabasePlatform instance that provides all the metadata about
	 * the platform this driver connects to.
	 *
	 * @return \Doctrine\DBAL\Platforms\AbstractPlatform The database platform.
	 */
	public function getDatabasePlatform() {
		return $this->connection->getDatabasePlatform();
	}

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 */
	public function dropTable($table) {
		$this->connection->dropTable($table);
	}

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 * @return bool
	 */
	public function tableExists($table) {
		return $this->connection->tableExists($table);
	}

	/**
	 * Espace a parameter to be used in a LIKE query
	 *
	 * @param string $param
	 * @return string
	 */
	public function escapeLikeParameter($param) {
		return $this->connection->escapeLikeParameter($param);
	}
}
