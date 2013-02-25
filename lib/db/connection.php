<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\Common\EventManager;

class Connection extends \Doctrine\DBAL\Connection {
	protected $table_prefix;
	protected $sequence_suffix;

	protected $adapter;

	/**
	 * Initializes a new instance of the Connection class.
	 *
	 * @param array $params  The connection parameters.
	 * @param Driver $driver
	 * @param Configuration $config
	 * @param EventManager $eventManager
	 */
	public function __construct(array $params, Driver $driver, Configuration $config = null,
		EventManager $eventManager = null)
	{
		if (!isset($params['adapter'])) {
			throw new Exception('adapter not set');
		}
		parent::__construct($params, $driver, $config, $eventManager);
		$this->adapter = new $params['adapter']($this);
	}

	/**
	 * Prepares an SQL statement.
	 *
	 * @param string $statement The SQL statement to prepare.
	 * @return \Doctrine\DBAL\Driver\Statement The prepared statement.
	 */
	public function prepare( $statement, $limit=null, $offset=null ) {
		// TODO: prefix
		// TODO: limit & offset
		// TODO: prepared statement cache
		return parent::prepare($statement);
	}

	/**
	 * Executes an, optionally parameterized, SQL query.
	 *
	 * If the query is parameterized, a prepared statement is used.
	 * If an SQLLogger is configured, the execution is logged.
	 *
	 * @param string $query The SQL query to execute.
	 * @param array $params The parameters to bind to the query, if any.
	 * @param array $types The types the previous parameters are in.
	 * @param QueryCacheProfile $qcp
	 * @return \Doctrine\DBAL\Driver\Statement The executed statement.
	 * @internal PERF: Directly prepares a driver statement, not a wrapper.
	 */
	public function executeQuery($query, array $params = array(), $types = array(), QueryCacheProfile $qcp = null)
	{
		// TODO: prefix
		return parent::executeQuery($query, $params, $types, $qcp);
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
	 * @internal PERF: Directly prepares a driver statement, not a wrapper.
	 */
	public function executeUpdate($query, array $params = array(), array $types = array())
	{
		// TODO: prefix
		return parent::executeUpdate($query, $params, $types);
	}
}
