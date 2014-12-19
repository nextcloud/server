<?php
/**
 * ownCloud
 *
 * @author Bart Visscher
 * @copyright 2013 Bart Visscher bartv@thisnet.nl
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * DBConnection interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * TODO: Description
 */
interface IDBConnection {
	/**
	 * Used to abstract the owncloud database access away
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @return \Doctrine\DBAL\Driver\Statement The prepared statement.
	 */
	public function prepare($sql, $limit=null, $offset=null);

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
	public function executeQuery($query, array $params = array(), $types = array());

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
	public function executeUpdate($query, array $params = array(), array $types = array());

	/**
	 * Used to get the id of the just inserted element
	 * @param string $table the name of the table where we inserted the item
	 * @return int the id of the inserted element
	 */
	public function lastInsertId($table = null);

	/**
	 * Insert a row if a matching row doesn't exists.
	 * @param string $table The table name (will replace *PREFIX*) to perform the replace on.
	 * @param array $input
	 * @throws \OC\HintException
	 *
	 * The input array if in the form:
	 *
	 * array ( 'id' => array ( 'value' => 6,
	 *	'key' => true
	 *	),
	 *	'name' => array ('value' => 'Stoyan'),
	 *	'family' => array ('value' => 'Stefanov'),
	 *	'birth_date' => array ('value' => '1975-06-20')
	 *	);
	 * @return bool
	 *
	 */
	public function insertIfNotExist($table, $input);

	/**
	 * Start a transaction
	 */
	public function beginTransaction();

	/**
	 * Commit the database changes done during a transaction that is in progress
	 */
	public function commit();

	/**
	 * Rollback the database changes done during a transaction that is in progress
	 */
	public function rollBack();

	/**
	 * Gets the error code and message as a string for logging
	 * @return string
	 */
	public function getError();

	/**
	 * Fetch the SQLSTATE associated with the last database operation.
	 *
	 * @return integer The last error code.
	 */
	public function errorCode();

	/**
	 * Fetch extended error information associated with the last database operation.
	 *
	 * @return array The last error information.
	 */
	public function errorInfo();

	/**
	 * Establishes the connection with the database.
	 *
	 * @return bool
	 */
	public function connect();

	/**
	 * Close the database connection
	 */
	public function close();

	/**
	 * Quotes a given input parameter.
	 *
	 * @param mixed $input Parameter to be quoted.
	 * @param int $type Type of the parameter.
	 * @return string The quoted parameter.
	 */
	public function quote($input, $type = \PDO::PARAM_STR);

	/**
	 * Gets the DatabasePlatform instance that provides all the metadata about
	 * the platform this driver connects to.
	 *
	 * @return \Doctrine\DBAL\Platforms\AbstractPlatform The database platform.
	 */
	public function getDatabasePlatform();

	/**
	 * Drop a table from the database if it exists
	 *
	 * @param string $table table name without the prefix
	 */
	public function dropTable($table);

	/**
	 * Check if a table exists
	 *
	 * @param string $table table name without the prefix
	 * @return bool
	 */
	public function tableExists($table);
}
