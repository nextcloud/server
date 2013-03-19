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

class DatabaseException extends Exception{
	private $query;

	public function __construct($message, $query){
		parent::__construct($message);
		$this->query = $query;
	}

	public function getQuery(){
		return $this->query;
	}
}

/**
 * This class manages the access to the database. It basically is a wrapper for
 * MDB2 with some adaptions.
 */
class OC_DB {
	const BACKEND_PDO=0;
	const BACKEND_MDB2=1;

	static private $preparedQueries = array();
	static private $cachingEnabled = true;

	/**
	 * @var MDB2_Driver_Common
	 */
	static private $connection; //the prefered connection to use, either PDO or MDB2
	static private $backend=null;
	/**
	 * @var MDB2_Driver_Common
	 */
	static private $MDB2=null;
	/**
	 * @var PDO
	 */
	static private $PDO=null;
	/**
	 * @var MDB2_Schema
	 */
	static private $schema=null;
	static private $inTransaction=false;
	static private $prefix=null;
	static private $type=null;

	/**
	 * check which backend we should use
	 * @return int BACKEND_MDB2 or BACKEND_PDO
	 */
	private static function getDBBackend() {
		//check if we can use PDO, else use MDB2 (installation always needs to be done my mdb2)
		if(class_exists('PDO') && OC_Config::getValue('installed', false)) {
			$type = OC_Config::getValue( "dbtype", "sqlite" );
			if($type=='oci') { //oracle also always needs mdb2
				return self::BACKEND_MDB2;
			}
			if($type=='sqlite3') $type='sqlite';
			$drivers=PDO::getAvailableDrivers();
			if(array_search($type, $drivers)!==false) {
				return self::BACKEND_PDO;
			}
		}
		return self::BACKEND_MDB2;
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
		if($backend==self::BACKEND_PDO) {
			$success = self::connectPDO();
			self::$connection=self::$PDO;
			self::$backend=self::BACKEND_PDO;
		}else{
			$success = self::connectMDB2();
			self::$connection=self::$MDB2;
			self::$backend=self::BACKEND_MDB2;
		}
		return $success;
	}

	/**
	 * connect to the database using pdo
	 *
	 * @return bool
	 */
	public static function connectPDO() {
		if(self::$connection) {
			if(self::$backend==self::BACKEND_MDB2) {
				self::disconnect();
			}else{
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
		}else{
			$port=false;
		}
		$opts = array();
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );

		// do nothing if the connection already has been established
		if(!self::$PDO) {
			// Add the dsn according to the database type
			switch($type) {
				case 'sqlite':
					$dsn='sqlite2:'.$datadir.'/'.$name.'.db';
					break;
				case 'sqlite3':
					$dsn='sqlite:'.$datadir.'/'.$name.'.db';
					break;
				case 'mysql':
					if($port) {
						$dsn='mysql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='mysql:dbname='.$name.';host='.$host;
					}
					$opts[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
					break;
				case 'pgsql':
					if($port) {
						$dsn='pgsql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='pgsql:dbname='.$name.';host='.$host;
					}
					/**
					* Ugly fix for pg connections pbm when password use spaces
					*/
					$e_user = addslashes($user);
					$e_password = addslashes($pass);
					$pass = $user = null;
					$dsn .= ";user='$e_user';password='$e_password'";
					/** END OF FIX***/
					break;
				case 'oci': // Oracle with PDO is unsupported
					if ($port) {
							$dsn = 'oci:dbname=//' . $host . ':' . $port . '/' . $name;
					} else {
							$dsn = 'oci:dbname=//' . $host . '/' . $name;
					}
					break;
                case 'mssql':
					if ($port) {
							$dsn='sqlsrv:Server='.$host.','.$port.';Database='.$name;
					} else {
							$dsn='sqlsrv:Server='.$host.';Database='.$name;
					}
					break;                    
				default:
					return false;
			}
			try{
				self::$PDO=new PDO($dsn, $user, $pass, $opts);
			}catch(PDOException $e) {
				OC_Log::write('core', $e->getMessage(), OC_Log::FATAL);
				OC_User::setUserId(null);

				// send http status 503
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				OC_Template::printErrorPage('Failed to connect to database');
				die();
			}
			// We always, really always want associative arrays
			self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			self::$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		return true;
	}

	/**
	 * connect to the database using mdb2
	 */
	public static function connectMDB2() {
		if(self::$connection) {
			if(self::$backend==self::BACKEND_PDO) {
				self::disconnect();
			}else{
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
		$SERVERROOT=OC::$SERVERROOT;
		$datadir=OC_Config::getValue( "datadirectory", "$SERVERROOT/data" );

		// do nothing if the connection already has been established
		if(!self::$MDB2) {
			// Require MDB2.php (not required in the head of the file so we only load it when needed)
			require_once 'MDB2.php';

			// Prepare options array
			$options = array(
					'portability' => MDB2_PORTABILITY_ALL - MDB2_PORTABILITY_FIX_CASE,
					'log_line_break' => '<br>',
					'idxname_format' => '%s',
					'debug' => true,
					'quote_identifier' => true
					);

			// Add the dsn according to the database type
			switch($type) {
				case 'sqlite':
				case 'sqlite3':
					$dsn = array(
						'phptype'  => $type,
						'database' => "$datadir/$name.db",
						'mode' => '0644'
					);
					break;
				case 'mysql':
					$dsn = array(
						'phptype'  => 'mysql',
						'username' => $user,
						'password' => $pass,
						'hostspec' => $host,
						'database' => $name
					);
					break;
				case 'pgsql':
					$dsn = array(
						'phptype'  => 'pgsql',
						'username' => $user,
						'password' => $pass,
						'hostspec' => $host,
						'database' => $name
					);
					break;
				case 'oci':
					$dsn = array(
							'phptype'  => 'oci8',
							'username' => $user,
							'password' => $pass,
							'charset' => 'AL32UTF8',
					);
					if ($host != '') {
						$dsn['hostspec'] = $host;
						$dsn['database'] = $name;
					} else { // use dbname for hostspec
						$dsn['hostspec'] = $name;
						$dsn['database'] = $user;
					}
					break;
				case 'mssql':
					$dsn = array(
						'phptype' => 'sqlsrv',
						'username' => $user,
						'password' => $pass,
						'hostspec' => $host,
						'database' => $name,
						'charset' => 'UTF-8'
					);
					$options['portability'] = $options['portability'] - MDB2_PORTABILITY_EMPTY_TO_NULL;
					break;
				default:
					return false;
			}

			// Try to establish connection
			self::$MDB2 = MDB2::factory( $dsn, $options );

			// Die if we could not connect
			if( PEAR::isError( self::$MDB2 )) {
				OC_Log::write('core', self::$MDB2->getUserInfo(), OC_Log::FATAL);
				OC_Log::write('core', self::$MDB2->getMessage(), OC_Log::FATAL);
				OC_User::setUserId(null);

				// send http status 503
				header('HTTP/1.1 503 Service Temporarily Unavailable');
				header('Status: 503 Service Temporarily Unavailable');
				OC_Template::printErrorPage('Failed to connect to database');
				die();
			}

			// We always, really always want associative arrays
			self::$MDB2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		}

		// we are done. great!
		return true;
	}

	/**
	 * @brief Prepare a SQL query
	 * @param string $query Query string
	 * @param int $limit
	 * @param int $offset
	 * @return MDB2_Statement_Common prepared SQL query
	 *
	 * SQL query via MDB2 prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query , $limit=null, $offset=null ) {

		if (!is_null($limit) && $limit != -1) {
			if (self::$backend == self::BACKEND_MDB2) {
				//MDB2 uses or emulates limits & offset internally
				self::$MDB2->setLimit($limit, $offset);
			} else {
				//PDO does not handle limit and offset.
				//FIXME: check limit notation for other dbs
				//the following sql thus might needs to take into account db ways of representing it
				//(oracle has no LIMIT / OFFSET)
				$limit = (int)$limit;
				$limitsql = ' LIMIT ' . $limit;
				if (!is_null($offset)) {
					$offset = (int)$offset;
					$limitsql .= ' OFFSET ' . $offset;
				}
				//insert limitsql
				if (substr($query, -1) == ';') { //if query ends with ;
					$query = substr($query, 0, -1) . $limitsql . ';';
				} else {
					$query.=$limitsql;
				}
			}
		} else {
			if (isset(self::$preparedQueries[$query]) and self::$cachingEnabled) {
				return self::$preparedQueries[$query];
			}
		}
		$rawQuery = $query;

		// Optimize the query
		$query = self::processQuery( $query );

		self::connect();
		// return the result
		if(self::$backend==self::BACKEND_MDB2) {
			$result = self::$connection->prepare( $query );

			// Die if we have an error (error means: bad query, not 0 results!)
			if( PEAR::isError($result)) {
				throw new DatabaseException($result->getMessage(), $query);
			}
		}else{
			try{
				$result=self::$connection->prepare($query);
			}catch(PDOException $e) {
				throw new DatabaseException($e->getMessage(), $query);
			}
			$result=new PDOStatementWrapper($result);
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
	 * @brief gets last value of autoincrement
	 * @param string $table The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @return int id
	 *
	 * MDB2 lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null) {
		self::connect();
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		if( $type == 'pgsql' ) {
			$query = self::prepare('SELECT lastval() AS id');
			$row = $query->execute()->fetchRow();
			return $row['id'];
		}
		if( $type == 'mssql' ) {
			if($table !== null) {
				$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
				$table = str_replace( '*PREFIX*', $prefix, $table );
			}
			return self::$connection->lastInsertId($table);
		}else{
			if($table !== null) {
				$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
				$suffix = OC_Config::getValue( "dbsequencesuffix", "_id_seq" );
				$table = str_replace( '*PREFIX*', $prefix, $table ).$suffix;
			}
			return self::$connection->lastInsertId($table);
		}
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
			if(self::$backend==self::BACKEND_MDB2) {
				self::$connection->disconnect();
			}
			self::$connection=false;
			self::$MDB2=false;
			self::$PDO=false;
		}

		return true;
	}

	/**
	 * @brief saves database scheme to xml file
	 * @param string $file name of file
	 * @param int $mode
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file, $mode=MDB2_SCHEMA_DUMP_STRUCTURE) {
		self::connectScheme();

		// write the scheme
		$definition = self::$schema->getDefinitionFromDatabase();
		$dump_options = array(
			'output_mode' => 'file',
			'output' => $file,
			'end_of_line' => "\n"
		);
		self::$schema->dumpDatabase( $definition, $dump_options, $mode );

		return true;
	}

	/**
	 * @brief Creates tables from XML file
	 * @param string $file file to read structure from
	 * @return bool
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $file ) {
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

		// cleanup the cached queries
		self::$preparedQueries = array();

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		/* FIXME: use CURRENT_TIMESTAMP for all databases. mysql supports it as a default for DATETIME since 5.6.5 [1]
		 * as a fallback we could use <default>0000-01-01 00:00:00</default> everywhere
		 * [1] http://bugs.mysql.com/bug.php?id=27645
		 * http://dev.mysql.com/doc/refman/5.0/en/timestamp-initialization.html
		 * http://www.postgresql.org/docs/8.1/static/functions-datetime.html
		 * http://www.sqlite.org/lang_createtable.html
		 * http://docs.oracle.com/cd/B19306_01/server.102/b14200/functions037.htm
		 */
		if( $CONFIG_DBTYPE == 'pgsql' ) { //mysql support it too but sqlite doesn't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>',
				'<default>CURRENT_TIMESTAMP</default>', $content );
		}

		file_put_contents( $file2, $content );

		// Try to create tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );

		//clean up memory
		unlink( $file2 );

		// Die in case something went wrong
		if( $definition instanceof MDB2_Schema_Error ) {
			OC_Template::printErrorPage( $definition->getMessage().': '.$definition->getUserInfo() );
		}
		if(OC_Config::getValue('dbtype', 'sqlite')==='oci') {
			unset($definition['charset']); //or MDB2 tries SHUTDOWN IMMEDIATE
			$oldname = $definition['name'];
			$definition['name']=OC_Config::getValue( "dbuser", $oldname );
		}

		// we should never drop a database
		$definition['overwrite'] = false;

		$ret=self::$schema->createDatabase( $definition );

		// Die in case something went wrong
		if( $ret instanceof MDB2_Error ) {
			OC_Template::printErrorPage( self::$MDB2->getDebugOutput().' '.$ret->getMessage() . ': '
				. $ret->getUserInfo() );
		}

		return true;
	}

	/**
	 * @brief update the database scheme
	 * @param string $file file to read structure from
	 * @return bool
	 */
	public static function updateDbFromStructure($file) {
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		$previousSchema = self::$schema->getDefinitionFromDatabase();
		if (PEAR::isError($previousSchema)) {
			$error = $previousSchema->getMessage();
			$detail = $previousSchema->getDebugInfo();
			$message = 'Failed to get existing database structure for updating ('.$error.', '.$detail.')';
			OC_Log::write('core', $message, OC_Log::FATAL);
			throw new Exception($message);
		}

		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $previousSchema['name'], $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		/* FIXME: use CURRENT_TIMESTAMP for all databases. mysql supports it as a default for DATETIME since 5.6.5 [1]
		 * as a fallback we could use <default>0000-01-01 00:00:00</default> everywhere
		 * [1] http://bugs.mysql.com/bug.php?id=27645
		 * http://dev.mysql.com/doc/refman/5.0/en/timestamp-initialization.html
		 * http://www.postgresql.org/docs/8.1/static/functions-datetime.html
		 * http://www.sqlite.org/lang_createtable.html
		 * http://docs.oracle.com/cd/B19306_01/server.102/b14200/functions037.htm
		 */
		if( $CONFIG_DBTYPE == 'pgsql' ) { //mysql support it too but sqlite doesn't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>',
				'<default>CURRENT_TIMESTAMP</default>', $content );
		}
		file_put_contents( $file2, $content );
		$op = self::$schema->updateDatabase($file2, $previousSchema, array(), false);

		//clean up memory
		unlink( $file2 );

		if (PEAR::isError($op)) {
			$error = $op->getMessage();
			$detail = $op->getDebugInfo();
			$message = 'Failed to update database structure ('.$error.', '.$detail.')';
			OC_Log::write('core', $message, OC_Log::FATAL);
			throw new Exception($message);
		}
		return true;
	}

	/**
	 * @brief connects to a MDB2 database scheme
	 * @returns bool
	 *
	 * Connects to a MDB2 database scheme
	 */
	private static function connectScheme() {
		// We need a mdb2 database connection
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->loadModule('Reverse');

		// Connect if this did not happen before
		if(!self::$schema) {
			require_once 'MDB2/Schema.php';
			self::$schema=MDB2_Schema::factory(self::$MDB2);
		}

		return true;
	}

	/**
	 * @brief Insert a row if a matching row doesn't exists.
	 * @param string $table. The table to insert into in the form '*PREFIX*tableName'
	 * @param array $input. An array of fieldname/value pairs
	 * @returns The return value from PDOStatementWrapper->execute()
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
		// differences in escaping of table names ('`' for mysql) and getting the current timestamp
		if( $type == 'sqlite' || $type == 'sqlite3' ) {
			// NOTE: For SQLite we have to use this clumsy approach
			// otherwise all fieldnames used must have a unique key.
			$query = 'SELECT * FROM "' . $table . '" WHERE ';
			foreach($input as $key => $value) {
				$query .= $key . " = '" . $value . '\' AND ';
			}
			$query = substr($query, 0, strlen($query) - 5);
			try {
				$stmt = self::prepare($query);
				$result = $stmt->execute();
			} catch(PDOException $e) {
				$entry = 'DB Error: "'.$e->getMessage() . '"<br />';
				$entry .= 'Offending command was: ' . $query . '<br />';
				OC_Log::write('core', $entry, OC_Log::FATAL);
				error_log('DB error: '.$entry);
				OC_Template::printErrorPage( $entry );
			}

			if($result->numRows() == 0) {
				$query = 'INSERT INTO "' . $table . '" ("'
					. implode('","', array_keys($input)) . '") VALUES("'
					. implode('","', array_values($input)) . '")';
			} else {
				return true;
			}
		} elseif( $type == 'pgsql' || $type == 'oci' || $type == 'mysql' || $type == 'mssql') {
			$query = 'INSERT INTO `' .$table . '` ('
				. implode(',', array_keys($input)) . ') SELECT \''
				. implode('\',\'', array_values($input)) . '\' FROM ' . $table . ' WHERE ';

			foreach($input as $key => $value) {
				$query .= $key . " = '" . $value . '\' AND ';
			}
			$query = substr($query, 0, strlen($query) - 5);
			$query .= ' HAVING COUNT(*) = 0';
		}

		// TODO: oci should be use " (quote) instead of ` (backtick).
		//OC_Log::write('core', __METHOD__ . ', type: ' . $type . ', query: ' . $query, OC_Log::DEBUG);

		try {
			$result = self::prepare($query);
		} catch(PDOException $e) {
			$entry = 'DB Error: "'.$e->getMessage() . '"<br />';
			$entry .= 'Offending command was: ' . $query.'<br />';
			OC_Log::write('core', $entry, OC_Log::FATAL);
			error_log('DB error: ' . $entry);
			OC_Template::printErrorPage( $entry );
		}

		return $result->execute();
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
		}elseif( $type == 'pgsql' ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'UNIX_TIMESTAMP()', 'cast(extract(epoch from current_timestamp) as integer)',
				$query );
		}elseif( $type == 'oci'  ) {
			$query = str_replace( '`', '"', $query );
			$query = str_ireplace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
		}elseif( $type == 'mssql' ) {
			$query = preg_replace( "/\`(.*?)`/", "[$1]", $query );
			$query = str_replace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
			$query = str_replace( 'now()', 'CURRENT_TIMESTAMP', $query );
			$query = str_replace( 'LENGTH(', 'LEN(', $query );
			$query = str_replace( 'SUBSTR(', 'SUBSTRING(', $query );
            
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
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->dropTable($tableName);
	}

	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($file) {
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to a temporary file
		$file2 = tempnam( get_temp_dir(), 'oc_db_scheme_' );
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		file_put_contents( $file2, $content );

		// get the tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );

		// Delete our temporary file
		unlink( $file2 );
		$tables=array_keys($definition['tables']);
		foreach($tables as $table) {
			self::dropTable($table);
		}
	}

	/**
	 * @brief replaces the owncloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	public static function replaceDB( $file ) {
		$apps = OC_App::getAllApps();
		self::beginTransaction();
		// Delete the old tables
		self::removeDBStructure( OC::$SERVERROOT . '/db_structure.xml' );

		foreach($apps as $app) {
			$path = OC_App::getAppPath($app).'/appinfo/database.xml';
			if(file_exists($path)) {
				self::removeDBStructure( $path );
			}
		}

		// Create new tables
		self::createDBFromStructure( $file );
		self::commit();
	}

	/**
	 * Start a transaction
	 * @return bool
	 */
	public static function beginTransaction() {
		self::connect();
		if (self::$backend==self::BACKEND_MDB2 && !self::$connection->supports('transactions')) {
			return false;
		}
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
	 * check if a result is an error, works with MDB2 and PDOException
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result) {
		if(!$result) {
			return true;
		}elseif(self::$backend==self::BACKEND_MDB2 and PEAR::isError($result)) {
			return true;
		}else{
			return false;
		}
	}

	/**
	 * returns the error code and message as a string for logging
	 * works with MDB2 and PDOException
	 * @param mixed $error
	 * @return string
	 */
	public static function getErrorMessage($error) {
		if ( self::$backend==self::BACKEND_MDB2 and PEAR::isError($error) ) {
			$msg = $error->getCode() . ': ' . $error->getMessage();
			if (defined('DEBUG') && DEBUG) {
				$msg .= '(' . $error->getDebugInfo() . ')';
			}
		} elseif (self::$backend==self::BACKEND_PDO and self::$PDO) {
			$msg = self::$PDO->errorCode() . ': ';
			$errorInfo = self::$PDO->errorInfo();
			if (is_array($errorInfo)) {
				$msg .= 'SQLSTATE = '.$errorInfo[0] . ', ';
				$msg .= 'Driver Code = '.$errorInfo[1] . ', ';
				$msg .= 'Driver Message = '.$errorInfo[2];
			}else{
				$msg = '';
			}
		}else{
			$msg = '';
		}
		return $msg;
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

/**
 * small wrapper around PDOStatement to make it behave ,more like an MDB2 Statement
 */
class PDOStatementWrapper{
	/**
	 * @var PDOStatement
	 */
	private $statement=null;
	private $lastArguments=array();

	public function __construct($statement) {
		$this->statement=$statement;
	}

	/**
	 * make execute return the result instead of a bool
	 */
	public function execute($input=array()) {
		$this->lastArguments = $input;
		if (count($input) > 0) {

			if (!isset($type)) {
				$type = OC_Config::getValue( "dbtype", "sqlite" );
			}

			if ($type == 'mssql') {
				$input = $this->tryFixSubstringLastArgumentDataForMSSQL($input);
			}

			$result=$this->statement->execute($input);
		} else {
			$result=$this->statement->execute();
		}
		
		if ($result) {
			return $this;
		} else {
			return false;
		}
	}

	private function tryFixSubstringLastArgumentDataForMSSQL($input) {
		$query = $this->statement->queryString;
		$pos = stripos ($query, 'SUBSTRING');

		if ( $pos === false) {
			return;
		}

		try {
			$newQuery = '';

			$cArg = 0;

			$inSubstring = false;

			// Create new query
			for ($i = 0; $i < strlen ($query); $i++) {
				if ($inSubstring == false) {
					// Defines when we should start inserting values
					if (substr ($query, $i, 9) == 'SUBSTRING') {
						$inSubstring = true;
					}
				} else {
					// Defines when we should stop inserting values
					if (substr ($query, $i, 1) == ')') {
						$inSubstring = false;
					}
				}

				if (substr ($query, $i, 1) == '?') {
					// We found a question mark
					if ($inSubstring) {
						$newQuery .= $input[$cArg];

						//
						// Remove from input array
						//
						array_splice ($input, $cArg, 1);
					} else {
						$newQuery .= substr ($query, $i, 1);
						$cArg++;
					}
				} else {
					$newQuery .= substr ($query, $i, 1);
				}
			}

			// The global data we need
			$name = OC_Config::getValue( "dbname", "owncloud" );
			$host = OC_Config::getValue( "dbhost", "" );
			$user = OC_Config::getValue( "dbuser", "" );
			$pass = OC_Config::getValue( "dbpassword", "" );
			if (strpos($host,':')) {
				list($host, $port) = explode(':', $host, 2);
			} else {
				$port = false;
			}
			$opts = array();

			if ($port) {
				$dsn = 'sqlsrv:Server='.$host.','.$port.';Database='.$name;
			} else {
				$dsn = 'sqlsrv:Server='.$host.';Database='.$name;
			}

			$PDO = new PDO($dsn, $user, $pass, $opts);
			$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			$PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$this->statement = $PDO->prepare($newQuery);

			$this->lastArguments = $input;

			return $input;
		} catch (PDOException $e){
			$entry = 'PDO DB Error: "'.$e->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$this->statement->queryString .'<br />';
			$entry .= 'Input parameters: ' .print_r($input, true).'<br />';
			$entry .= 'Stack trace: ' .$e->getTraceAsString().'<br />';
			OC_Log::write('core', $entry, OC_Log::FATAL);
			OC_User::setUserId(null);

			// send http status 503
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			header('Status: 503 Service Temporarily Unavailable');
			OC_Template::printErrorPage('Failed to connect to database');
			die ($entry);
		}
	}
    
	/**
	 * provide numRows
	 */
	public function numRows() {
		$regex = '/^SELECT\s+(?:ALL\s+|DISTINCT\s+)?(?:.*?)\s+FROM\s+(.*)$/i';
		if (preg_match($regex, $this->statement->queryString, $output) > 0) {
			$query = OC_DB::prepare("SELECT COUNT(*) FROM {$output[1]}", PDO::FETCH_NUM);
			return $query->execute($this->lastArguments)->fetchColumn();
		}else{
			return $this->statement->rowCount();
		}
	}

	/**
	 * provide an alias for fetch
	 */
	public function fetchRow() {
		return $this->statement->fetch();
	}

	/**
	 * pass all other function directly to the PDOStatement
	 */
	public function __call($name, $arguments) {
		return call_user_func_array(array($this->statement, $name), $arguments);
	}

	/**
	 * Provide a simple fetchOne.
	 * fetch single column from the next row
	 * @param int $colnum the column number to fetch
	 */
	public function fetchOne($colnum = 0) {
		return $this->statement->fetchColumn($colnum);
	}
}
