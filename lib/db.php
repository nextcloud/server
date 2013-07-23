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
	const BACKEND_DOCTRINE=2;

	static private $preparedQueries = array();
	static private $cachingEnabled = true;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	static private $connection; //the preferred connection to use, only Doctrine
	static private $backend=null;
	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	static private $DOCTRINE=null;

	static private $inTransaction=false;
	static private $prefix=null;
	static private $type=null;

	/**
	 * check which backend we should use
	 * @return int BACKEND_DOCTRINE
	 */
	private static function getDBBackend() {
		return self::BACKEND_DOCTRINE;
	}

	/**
	 * @brief connects to the database
	 * @param int $backend
	 * @return bool true if connection can be established or false on error
	 *
	 * Connects to the database as specified in config.php
	 */
	public static function connect($backend=null) {
		if(self::$connection) {
			return true;
		}
		if(is_null($backend)) {
			$backend=self::getDBBackend();
		}
		if($backend==self::BACKEND_DOCTRINE) {
			$success = self::connectDoctrine();
			self::$connection=self::$DOCTRINE;
			self::$backend=self::BACKEND_DOCTRINE;
		}
		return $success;
	}

	/**
	 * connect to the database using doctrine
	 *
	 * @return bool
	 */
	public static function connectDoctrine() {
		if(self::$connection) {
			if(self::$backend!=self::BACKEND_DOCTRINE) {
				self::disconnect();
			} else {
				return true;
			}
		}
		self::$preparedQueries = array();
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
		if(!self::$DOCTRINE) {
			$config = new \Doctrine\DBAL\Configuration();
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
					break;
				default:
					return false;
			}
			try {
				self::$DOCTRINE = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $config);
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

		if (!is_null($limit) && $limit != -1) {
			if ($limit === -1) {
				$limit = null;
			}
			$platform = self::$connection->getDatabasePlatform();
			$query = $platform->modifyLimitQuery($query, $limit, $offset);
		} else {
			if (isset(self::$preparedQueries[$query]) and self::$cachingEnabled) {
				return self::$preparedQueries[$query];
			}
		}
		$rawQuery = $query;

		// Optimize the query
		$query = self::processQuery( $query );
		if(OC_Config::getValue( "log_query", false)) {
			OC_Log::write('core', 'DB prepare : '.$query, OC_Log::DEBUG);
		}
		self::connect();
		
		if ($isManipulation === null) {
			//try to guess, so we return the number of rows on manipulations
			$isManipulation = self::isManipulation($query);
		}
		
		// return the result
		if (self::$backend == self::BACKEND_DOCTRINE) {
			try {
				$result=self::$connection->prepare($query);
			} catch(\Doctrine\DBAL\DBALException $e) {
				throw new \DatabaseException($e->getMessage(), $query);
			}
			// differentiate between query and manipulation
			$result=new OC_DB_StatementWrapper($result, $isManipulation);
		}
		if ((is_null($limit) || $limit == -1) and self::$cachingEnabled ) {
			$type = OC_Config::getValue( "dbtype", "sqlite" );
			if( $type != 'sqlite' && $type != 'sqlite3' ) {
				self::$preparedQueries[$rawQuery] = $result;
			}
		}
		return $result;
	}
	
	/**
	 * tries to guess the type of statement based on the first 10 characters
	 * the current check allows some whitespace but does not work with IF EXISTS or other more complex statements
	 * 
	 * @param string $sql
	 */
	static public function isManipulation( $sql ) {
		$selectOccurence = stripos ($sql, "SELECT");
		if ($selectOccurence !== false && $selectOccurence < 10) {
			return false;
		}
		$insertOccurence = stripos ($sql, "INSERT");
		if ($insertOccurence !== false && $insertOccurence < 10) {
			return true;
		}
		$updateOccurence = stripos ($sql, "UPDATE");
		if ($updateOccurence !== false && $updateOccurence < 10) {
			return true;
		}
		$deleteOccurance = stripos ($sql, "DELETE");
		if ($deleteOccurance !== false && $deleteOccurance < 10) {
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
			if (stripos($stmt,'LIMIT') !== false) { //OFFSET requires LIMIT, se we only neet to check for LIMIT
				// TODO try to convert LIMIT OFFSET notation to parameters, see fixLimitClauseForMSSQL
				$message = 'LIMIT and OFFSET are forbidden for portability reasons,'
						 . ' pass an array with \'limit\' and \'offset\' instead';
				throw new DatabaseException($message);
			}
			$stmt = array('sql' => $stmt, 'limit' => null, 'offset' => null);
		}
		if (is_array($stmt)){
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
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		if( $type === 'pgsql' ) {
			$result = self::executeAudited('SELECT lastval() AS id');
			$row = $result->fetchRow();
			self::raiseExceptionOnError($row, 'fetching row for insertid failed');
			return $row['id'];
		} else if( $type === 'mssql') {
			if($table !== null) {
				$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
				$table = str_replace( '*PREFIX*', $prefix, $table );
			}
			return self::$connection->lastInsertId($table);
		}
		if( $type === 'oci' ) {
			if($table !== null) {
				$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
				$suffix = '_SEQ';
				$table = '"'.str_replace( '*PREFIX*', $prefix, $table ).$suffix.'"';
			}
			return self::$connection->lastInsertId($table);
		} else {
			if($table !== null) {
				$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
				$suffix = OC_Config::getValue( "dbsequencesuffix", "_id_seq" );
				$table = str_replace( '*PREFIX*', $prefix, $table ).$suffix;
			}
			$result = self::$connection->lastInsertId($table);
		}
		self::raiseExceptionOnError($result, 'insertid failed');
		return $result;
	}

	/**
	 * @brief Disconnect
	 * @return bool
	 *
	 * This is good bye, good bye, yeah!
	 */
	public static function disconnect() {
		// Cut connection if required
		if(self::$connection) {
			self::$connection=false;
			self::$DOCTRINE=false;
		}

		return true;
	}

	/** else {
	 * @brief saves database scheme to xml file
	 * @param string $file name of file
	 * @param int $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file, $mode=MDB2_SCHEMA_DUMP_STRUCTURE) {
		self::connectDoctrine();
		return OC_DB_Schema::getDbStructure(self::$DOCTRINE, $file);
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $file ) {
		self::connectDoctrine();
		return OC_DB_Schema::createDbFromStructure(self::$DOCTRINE, $file);
	}

	/**
	 * @brief update the database scheme
	 * @param string $file file to read structure from
	 * @throws Exception
	 * @return bool
	 */
	public static function updateDbFromStructure($file) {
		self::connectDoctrine();
		try {
			$result = OC_DB_Schema::updateDbFromStructure(self::$DOCTRINE, $file);
		} catch (Exception $e) {
			OC_Log::write('core', 'Failed to update database structure ('.$e.')', OC_Log::FATAL);
			throw $e;
		}
		return $result;
	}

	/**
	 * @brief Insert a row if a matching row doesn't exists.
	 * @param string $table. The table to insert into in the form '*PREFIX*tableName'
	 * @param array $input. An array of fieldname/value pairs
	 * @returns int number of updated rows
	 */
	public static function insertIfNotExist($table, $input) {
		self::connect();
		$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
		$table = str_replace( '*PREFIX*', $prefix, $table );

		if(is_null(self::$type)) {
			self::$type=OC_Config::getValue( "dbtype", "sqlite" );
		}
		$type = self::$type;

		$query = '';
		$inserts = array_values($input);
		// differences in escaping of table names ('`' for mysql) and getting the current timestamp
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// NOTE: For SQLite we have to use this clumsy approach
			// otherwise all fieldnames used must have a unique key.
			$query = 'SELECT * FROM `' . $table . '` WHERE ';
			foreach($input as $key => $value) {
				$query .= '`' . $key . '` = ? AND ';
			}
			$query = substr($query, 0, strlen($query) - 5);
			try {
				$result = self::executeAudited($query, $inserts);
			} catch(DatabaseException $e) {
				OC_Template::printExceptionErrorPage( $e );
			}

			if((int)$result->numRows() === 0) {
				$query = 'INSERT INTO `' . $table . '` (`'
					. implode('`,`', array_keys($input)) . '`) VALUES('
					. str_repeat('?,', count($input)-1).'? ' . ')';
			} else {
				return 0; //no rows updated
			}
		} elseif( $type == 'pgsql' || $type == 'oci' || $type == 'mysql' || $type == 'mssql') {
			$query = 'INSERT INTO `' .$table . '` (`'
				. implode('`,`', array_keys($input)) . '`) SELECT '
				. str_repeat('?,', count($input)-1).'? ' // Is there a prettier alternative?
				. 'FROM `' . $table . '` WHERE ';

			foreach($input as $key => $value) {
				$query .= '`' . $key . '` = ? AND ';
			}
			$query = substr($query, 0, strlen($query) - 5);
			$query .= ' HAVING COUNT(*) = 0';
			$inserts = array_merge($inserts, $inserts);
		}

		try {
			$result = self::executeAudited($query, $inserts);
		} catch(\Doctrine\DBAL\DBALException $e) {
			OC_Template::printExceptionErrorPage( $e );
		}

		return $result;
	}

	/**
	 * @brief does minor changes to query
	 * @param string $query Query string
	 * @return string corrected query string
	 *
	 * This function replaces *PREFIX* with the value of $CONFIG_DBTABLEPREFIX
	 * and replaces the ` with ' or " according to the database driver.
	 */
	private static function processQuery( $query ) {
		self::connect();
		// We need Database type and table prefix
		if(is_null(self::$type)) {
			self::$type=OC_Config::getValue( "dbtype", "sqlite" );
		}
		$type = self::$type;
		if(is_null(self::$prefix)) {
			self::$prefix=OC_Config::getValue( "dbtableprefix", "oc_" );
		}
		$prefix = self::$prefix;

		// differences in escaping of table names ('`' for mysql) and getting the current timestamp
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'NOW()', 'datetime(\'now\')', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'strftime(\'%s\',\'now\')', $query );
		} elseif( $type == 'pgsql' ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'cast(extract(epoch from current_timestamp) as integer)',
				$query );
		} elseif( $type == 'oci'  ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', "(cast(sys_extract_utc(systimestamp) as date) - date'1970-01-01') * 86400", $query );
		}elseif( $type == 'mssql' ) {
			$query = preg_replace( "/\`(.*?)`/", "[$1]", $query );
			$query = str_ireplace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
			$query = str_replace( 'LENGTH(', 'LEN(', $query );
			$query = str_replace( 'SUBSTR(', 'SUBSTRING(', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'DATEDIFF(second,{d \'1970-01-01\'},GETDATE())', $query );

			$query = self::fixLimitClauseForMSSQL($query);
		}

		// replace table name prefix
		$query = str_replace( '*PREFIX*', $prefix, $query );

		return $query;
	}

	private static function fixLimitClauseForMSSQL($query) {
		$limitLocation = stripos ($query, "LIMIT");

		if ( $limitLocation === false ) {
			return $query;
		} 

		// total == 0 means all results - not zero results
		//
		// First number is either total or offset, locate it by first space
		//
		$offset = substr ($query, $limitLocation + 5);
		$offset = substr ($offset, 0, stripos ($offset, ' '));
		$offset = trim ($offset);

		// check for another parameter
		if (stripos ($offset, ',') === false) {
			// no more parameters
			$offset = 0;
			$total = intval ($offset);
		} else {
			// found another parameter
			$offset = intval ($offset);

			$total = substr ($query, $limitLocation + 5);
			$total = substr ($total, stripos ($total, ','));

			$total = substr ($total, 0, stripos ($total, ' '));
			$total = intval ($total);
		}

		$query = trim (substr ($query, 0, $limitLocation));

		if ($offset == 0 && $total !== 0) {
			if (strpos($query, "SELECT") === false) {
				$query = "TOP {$total} " . $query;
			} else {
				$query = preg_replace('/SELECT(\s*DISTINCT)?/Dsi', 'SELECT$1 TOP '.$total, $query);
			}
		} else if ($offset > 0) {
			$query = preg_replace('/SELECT(\s*DISTINCT)?/Dsi', 'SELECT$1 TOP(10000000) ', $query);
			$query = 'SELECT *
					FROM (SELECT sub2.*, ROW_NUMBER() OVER(ORDER BY sub2.line2) AS line3
					FROM (SELECT 1 AS line2, sub1.* FROM (' . $query . ') AS sub1) as sub2) AS sub3';

			if ($total > 0) {
				$query .= ' WHERE line3 BETWEEN ' . ($offset + 1) . ' AND ' . ($offset + $total);
			} else {
				$query .= ' WHERE line3 > ' . $offset;
			}
		}
		return $query;
	}

	/**
	 * @brief drop a table
	 * @param string $tableName the table to drop
	 */
	public static function dropTable($tableName) {
		self::connectDoctrine();
		OC_DB_Schema::dropTable(self::$DOCTRINE, $tableName);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($file) {
		self::connectDoctrine();
		OC_DB_Schema::removeDBStructure(self::$DOCTRINE, $file);
	}

	/**
	 * @brief replaces the ownCloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public static function replaceDB( $file ) {
		self::connectDoctrine();
		OC_DB_Schema::replaceDB(self::$DOCTRINE, $file);
	}

	/**
	 * Start a transaction
	 * @return bool
	 */
	public static function beginTransaction() {
		self::connect();
		self::$connection->beginTransaction();
		self::$inTransaction=true;
		return true;
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 * @return bool
	 */
	public static function commit() {
		self::connect();
		if(!self::$inTransaction) {
			return false;
		}
		self::$connection->commit();
		self::$inTransaction=false;
		return true;
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
		if (self::$backend==self::BACKEND_DOCTRINE and self::$DOCTRINE) {
			$msg = self::$DOCTRINE->errorCode() . ': ';
			$errorInfo = self::$DOCTRINE->errorInfo();
			if (is_array($errorInfo)) {
				$msg .= 'SQLSTATE = '.$errorInfo[0] . ', ';
				$msg .= 'Driver Code = '.$errorInfo[1] . ', ';
				$msg .= 'Driver Message = '.$errorInfo[2];
			}
			return $msg;
		}

		return '';
	}

	/**
	 * @param bool $enabled
	 */
	static public function enableCaching($enabled) {
		if (!$enabled) {
			self::$preparedQueries = array();
		}
		self::$cachingEnabled = $enabled;
	}
}
