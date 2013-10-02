<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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

define('MDB2_SCHEMA_DUMP_STRUCTURE', '1');

class DatabaseException extends Exception {
	private $query;

	//FIXME getQuery seems to be unused, maybe use parent constructor with $message, $code and $previous
	public function __construct($message, $query = null){
		parent::__construct($message);
		$this->query = $query;
	}

	public function getQuery() {
		return $this->query;
	}
}

/**
 * This class manages the access to the database. It basically is a wrapper for
 * Doctrine with some adaptions.
 */
class OC_DB {
	/**
	 * @var \OC\DB\Connection $connection
	 */
	static private $connection; //the prefered connection to use, only Doctrine

	static private $prefix=null;
	static private $type=null;

	/**
	 * @brief connects to the database
	 * @return bool true if connection can be established or false on error
	 *
	 * Connects to the database as specified in config.php
	 */
	public static function connect() {
		if(self::$connection) {
			return true;
		}

		// The global data we need
		$name = OC_Config::getValue( "dbname", "owncloud" );
		$host = OC_Config::getValue( "dbhost", "" );
		$user = OC_Config::getValue( "dbuser", "" );
		$pass = OC_Config::getValue( "dbpassword", "" );
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		if(strpos($host, ':')) {
			list($host, $port)=explode(':', $host, 2);
		} else {
			$port=false;
		}

		// do nothing if the connection already has been established
		if (!self::$connection) {
			$config = new \Doctrine\DBAL\Configuration();
			$eventManager = new \Doctrine\Common\EventManager();
			switch($type) {
				case 'sqlite':
				case 'sqlite3':
					$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );
					$connectionParams = array(
							'user' => $user,
							'password' => $pass,
							'path' => $datadir.'/'.$name.'.db',
							'driver' => 'pdo_sqlite',
					);
					$connectionParams['adapter'] = '\OC\DB\AdapterSqlite';
					$connectionParams['wrapperClass'] = 'OC\DB\Connection';
					break;
				case 'mysql':
					$connectionParams = array(
							'user' => $user,
							'password' => $pass,
							'host' => $host,
							'port' => $port,
							'dbname' => $name,
							'charset' => 'UTF8',
							'driver' => 'pdo_mysql',
					);
					$connectionParams['adapter'] = '\OC\DB\Adapter';
					$connectionParams['wrapperClass'] = 'OC\DB\Connection';
					break;
				case 'pgsql':
					$connectionParams = array(
							'user' => $user,
							'password' => $pass,
							'host' => $host,
							'port' => $port,
							'dbname' => $name,
							'driver' => 'pdo_pgsql',
					);
					$connectionParams['adapter'] = '\OC\DB\AdapterPgSql';
					$connectionParams['wrapperClass'] = 'OC\DB\Connection';
					break;
				case 'oci':
					$connectionParams = array(
							'user' => $user,
							'password' => $pass,
							'host' => $host,
							'dbname' => $name,
							'charset' => 'AL32UTF8',
							'driver' => 'oci8',
					);
					if (!empty($port)) {
						$connectionParams['port'] = $port;
					}
					$connectionParams['adapter'] = '\OC\DB\AdapterOCI8';
					$connectionParams['wrapperClass'] = 'OC\DB\OracleConnection';
					$eventManager->addEventSubscriber(new \Doctrine\DBAL\Event\Listeners\OracleSessionInit);
					break;
				case 'mssql':
					$connectionParams = array(
							'user' => $user,
							'password' => $pass,
							'host' => $host,
							'port' => $port,
							'dbname' => $name,
							'charset' => 'UTF8',
							'driver' => 'pdo_sqlsrv',
					);
					$connectionParams['adapter'] = '\OC\DB\AdapterSQLSrv';
					$connectionParams['wrapperClass'] = 'OC\DB\Connection';
					break;
				default:
					return false;
			}
			$connectionParams['tablePrefix'] = OC_Config::getValue('dbtableprefix', 'oc_' );
			try {
				self::$connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config, $eventManager);
				if ($type === 'sqlite' || $type === 'sqlite3') {
					// Sqlite doesn't handle query caching and schema changes
					// TODO: find a better way to handle this
					self::$connection->disableQueryStatementCaching();
				}
			} catch(\Doctrine\DBAL\DBALException $e) {
				OC_Log::write('core', $e->getMessage(), OC_Log::FATAL);
				OC_User::setUserId(null);

				// send http status 503
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				OC_Template::printErrorPage('Failed to connect to database');
				die();
			}
		}
		return true;
	}

	/**
	 * @return \OC\DB\Connection
	 */
	static public function getConnection() {
		self::connect();
		return self::$connection;
	}

	/**
	 * get MDB2 schema manager
	 *
	 * @return \OC\DB\MDB2SchemaManager
	 */
	private static function getMDB2SchemaManager()
	{
		return new \OC\DB\MDB2SchemaManager(self::getConnection());
	}

	/**
	 * @brief Prepare a SQL query
	 * @param string $query Query string
	 * @param int $limit
	 * @param int $offset
	 * @param bool $isManipulation
	 * @throws DatabaseException
	 * @return \Doctrine\DBAL\Statement prepared SQL query
	 *
	 * SQL query via Doctrine prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query , $limit = null, $offset = null, $isManipulation = null) {
		self::connect();
		
		if ($isManipulation === null) {
			//try to guess, so we return the number of rows on manipulations
			$isManipulation = self::isManipulation($query);
		}
		
		// return the result
		try {
			$result = self::$connection->prepare($query, $limit, $offset);
		} catch (\Doctrine\DBAL\DBALException $e) {
			throw new \DatabaseException($e->getMessage(), $query);
		}
		// differentiate between query and manipulation
		$result = new OC_DB_StatementWrapper($result, $isManipulation);
		return $result;
	}

	/**
	 * tries to guess the type of statement based on the first 10 characters
	 * the current check allows some whitespace but does not work with IF EXISTS or other more complex statements
	 * 
	 * @param string $sql
	 * @return bool
	 */
	static public function isManipulation( $sql ) {
		$selectOccurrence = stripos($sql, 'SELECT');
		if ($selectOccurrence !== false && $selectOccurrence < 10) {
			return false;
		}
		$insertOccurrence = stripos($sql, 'INSERT');
		if ($insertOccurrence !== false && $insertOccurrence < 10) {
			return true;
		}
		$updateOccurrence = stripos($sql, 'UPDATE');
		if ($updateOccurrence !== false && $updateOccurrence < 10) {
			return true;
		}
		$deleteOccurrence = stripos($sql, 'DELETE');
		if ($deleteOccurrence !== false && $deleteOccurrence < 10) {
			return true;
		}
		return false;
	}
	
	/**
	 * @brief execute a prepared statement, on error write log and throw exception
	 * @param mixed $stmt OC_DB_StatementWrapper,
	 *					  an array with 'sql' and optionally 'limit' and 'offset' keys
	 *					.. or a simple sql query string
	 * @param array $parameters
	 * @return result
	 * @throws DatabaseException
	 */
	static public function executeAudited( $stmt, array $parameters = null) {
		if (is_string($stmt)) {
			// convert to an array with 'sql'
			if (stripos($stmt, 'LIMIT') !== false) { //OFFSET requires LIMIT, so we only need to check for LIMIT
				// TODO try to convert LIMIT OFFSET notation to parameters, see fixLimitClauseForMSSQL
				$message = 'LIMIT and OFFSET are forbidden for portability reasons,'
						 . ' pass an array with \'limit\' and \'offset\' instead';
				throw new DatabaseException($message);
			}
			$stmt = array('sql' => $stmt, 'limit' => null, 'offset' => null);
		}
		if (is_array($stmt)) {
			// convert to prepared statement
			if ( ! array_key_exists('sql', $stmt) ) {
				$message = 'statement array must at least contain key \'sql\'';
				throw new DatabaseException($message);
			}
			if ( ! array_key_exists('limit', $stmt) ) {
				$stmt['limit'] = null;
			}
			if ( ! array_key_exists('limit', $stmt) ) {
				$stmt['offset'] = null;
			}
			$stmt = self::prepare($stmt['sql'], $stmt['limit'], $stmt['offset']);
		}
		self::raiseExceptionOnError($stmt, 'Could not prepare statement');
		if ($stmt instanceof OC_DB_StatementWrapper) {
			$result = $stmt->execute($parameters);
			self::raiseExceptionOnError($result, 'Could not execute statement');
		} else {
			if (is_object($stmt)) {
				$message = 'Expected a prepared statement or array got ' . get_class($stmt);
			} else {
				$message = 'Expected a prepared statement or array got ' . gettype($stmt);
			}
			throw new DatabaseException($message);
		}
		return $result;
	}

	/**
	 * @brief gets last value of autoincrement
	 * @param string $table The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @return int id
	 * @throws DatabaseException
	 *
	 * \Doctrine\DBAL\Connection lastInsertId
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null) {
		self::connect();
		return self::$connection->lastInsertId($table);
	}

	/**
	 * @brief Insert a row if a matching row doesn't exists.
	 * @param string $table. The table to insert into in the form '*PREFIX*tableName'
	 * @param array $input. An array of fieldname/value pairs
	 * @return int number of updated rows
	 */
	public static function insertIfNotExist($table, $input) {
		self::connect();
		return self::$connection->insertIfNotExist($table, $input);
	}

	/**
	 * Start a transaction
	 */
	public static function beginTransaction() {
		self::connect();
		self::$connection->beginTransaction();
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 */
	public static function commit() {
		self::connect();
		self::$connection->commit();
	}

	/**
	 * @brief saves database schema to xml file
	 * @param string $file name of file
	 * @param int $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file, $mode = 0) {
		$schemaManager = self::getMDB2SchemaManager();
		return $schemaManager->getDbStructure($file);
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $file ) {
		$schemaManager = self::getMDB2SchemaManager();
		$result = $schemaManager->createDbFromStructure($file);
		return $result;
	}

	/**
	 * @brief update the database schema
	 * @param string $file file to read structure from
	 * @throws Exception
	 * @return bool
	 */
	public static function updateDbFromStructure($file) {
		$schemaManager = self::getMDB2SchemaManager();
		try {
			$result = $schemaManager->updateDbFromStructure($file);
		} catch (Exception $e) {
			OC_Log::write('core', 'Failed to update database structure ('.$e.')', OC_Log::FATAL);
			throw $e;
		}
		return $result;
	}

	/**
	 * @brief drop a table
	 * @param string $tableName the table to drop
	 */
	public static function dropTable($tableName) {
		$schemaManager = self::getMDB2SchemaManager();
		$schemaManager->dropTable($tableName);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($file) {
		$schemaManager = self::getMDB2SchemaManager();
		$schemaManager->removeDBStructure($file);
	}

	/**
	 * @brief replaces the ownCloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public static function replaceDB( $file ) {
		$schemaManager = self::getMDB2SchemaManager();
		$schemaManager->replaceDB($file);
	}

	/**
	 * check if a result is an error, works with Doctrine
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result) {
		//Doctrine returns false on error (and throws an exception)
		return $result === false;
	}
	/**
	 * check if a result is an error and throws an exception, works with \Doctrine\DBAL\DBALException
	 * @param mixed $result
	 * @param string $message
	 * @return void
	 * @throws DatabaseException
	 */
	public static function raiseExceptionOnError($result, $message = null) {
		if(self::isError($result)) {
			if ($message === null) {
				$message = self::getErrorMessage($result);
			} else {
				$message .= ', Root cause:' . self::getErrorMessage($result);
			}
			throw new DatabaseException($message, self::getErrorCode($result));
		}
	}

	public static function getErrorCode($error) {
		$code = self::$connection->errorCode();
		return $code;
	}
	/**
	 * returns the error code and message as a string for logging
	 * works with DoctrineException
	 * @param mixed $error
	 * @return string
	 */
	public static function getErrorMessage($error) {
		if (self::$connection) {
			return self::$connection->getError();
		}
		return '';
	}

	/**
	 * @param bool $enabled
	 */
	static public function enableCaching($enabled) {
		if ($enabled) {
			self::$connection->enableQueryStatementCaching();
		} else {
			self::$connection->disableQueryStatementCaching();
		}
	}
}
