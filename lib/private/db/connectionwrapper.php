<?php
/**
 * Copyright (c) 2013 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;


class ConnectionWrapper implements \OCP\IDBConnection {

	private $connection;

	public function __construct(Connection $conn) {
		$this->connection = $conn;
	}

	/**
	 * Used to the owncloud database access away
	 * @param string $sql the sql query with ? placeholder for params
	 * @param int $limit the maximum number of rows
	 * @param int $offset from which row we want to start
	 * @return \Doctrine\DBAL\Driver\Statement The prepared statement.
	 */
	public function prepare($sql, $limit = null, $offset = null)
	{
		return $this->connection->prepare($sql, $limit, $offset);
	}

	/**
	 * Used to get the id of the just inserted element
	 * @param string $table the name of the table where we inserted the item
	 * @return string the id of the inserted element
	 */
	public function lastInsertId($table = null)
	{
		return $this->connection->lastInsertId($table);
	}

	/**
	 * Insert a row if a matching row doesn't exists.
	 * @param string $table The table name (will replace *PREFIX*) to perform the replace on.
	 * @param array $input
	 *
	 * The input array if in the form:
	 *
	 * array ( 'id' => array ( 'value' => 6,
	 *    'key' => true
	 *    ),
	 *    'name' => array ('value' => 'Stoyan'),
	 *    'family' => array ('value' => 'Stefanov'),
	 *    'birth_date' => array ('value' => '1975-06-20')
	 *    );
	 * @return bool
	 *
	 */
	public function insertIfNotExist($table, $input)
	{
		return $this->connection->insertIfNotExist($table, $input);
	}

	/**
	 * Start a transaction
	 * @return bool TRUE on success or FALSE on failure
	 */
	public function beginTransaction()
	{
		return $this->connection->beginTransaction();
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 * @return bool TRUE on success or FALSE on failure
	 */
	public function commit()
	{
		return $this->connection->commit();
	}

	/**
	 * Rollback the database changes done during a transaction that is in progress
	 * @return bool TRUE on success or FALSE on failure
	 */
	public function rollBack()
	{
		return $this->connection->rollBack();
	}

	/**
	 * Gets the error code and message as a string for logging
	 * @return string
	 */
	public function getError()
	{
		return $this->connection->getError();
	}
}
