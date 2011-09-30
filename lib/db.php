<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * This class manages the access to the database. It basically is a wrapper for
 * MDB2 with some adaptions.
 */
class OC_DB {
	static private $DBConnection=false;
	static private $schema=false;
	static private $affected=0;
	static private $result=false;

	/**
	 * @brief connects to the database
	 * @returns true if connection can be established or nothing (die())
	 *
	 * Connects to the database as specified in config.php
	 */
	static public function connect(){
		// The global data we need
		$CONFIG_DBNAME = OC_Config::getValue( "dbname", "owncloud" );;
		$CONFIG_DBHOST = OC_Config::getValue( "dbhost", "" );;
		$CONFIG_DBUSER = OC_Config::getValue( "dbuser", "" );;
		$CONFIG_DBPASSWORD = OC_Config::getValue( "dbpassword", "" );;
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );;
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );

		// do nothing if the connection already has been established
		if(!self::$DBConnection){
			// Require MDB2.php (not required in the head of the file so we only load it when needed)
			require_once('MDB2.php');

			// Prepare options array
			$options = array(
			  'portability' => MDB2_PORTABILITY_ALL,
			  'log_line_break' => '<br>',
			  'idxname_format' => '%s',
			  'debug' => true,
			  'quote_identifier' => true  );

			// Add the dsn according to the database type
			if( $CONFIG_DBTYPE == 'sqlite' or $CONFIG_DBTYPE == 'sqlite3' ){
				// sqlite
				$dsn = array(
				  'phptype'  => $CONFIG_DBTYPE,
				  'database' => "$datadir/$CONFIG_DBNAME.db",
				  'mode' => '0644' );
			}
			elseif( $CONFIG_DBTYPE == 'mysql' ){
				// MySQL
				$dsn = array(
				  'phptype'  => 'mysql',
				  'username' => $CONFIG_DBUSER,
				  'password' => $CONFIG_DBPASSWORD,
				  'hostspec' => $CONFIG_DBHOST,
				  'database' => $CONFIG_DBNAME );
			}
			elseif( $CONFIG_DBTYPE == 'pgsql' ){
				// PostgreSQL
				$dsn = array(
				  'phptype'  => 'pgsql',
				  'username' => $CONFIG_DBUSER,
				  'password' => $CONFIG_DBPASSWORD,
				  'hostspec' => $CONFIG_DBHOST,
				  'database' => $CONFIG_DBNAME );
			}

			// Try to establish connection
			self::$DBConnection = MDB2::factory( $dsn, $options );

			// Die if we could not connect
			if( PEAR::isError( self::$DBConnection )){
				echo( '<b>can not connect to database, using '.$CONFIG_DBTYPE.'. ('.self::$DBConnection->getUserInfo().')</center>');
				$error = self::$DBConnection->getMessage();
				if(defined("DEBUG") && DEBUG) {error_log( $error);}
				if(defined("DEBUG") && DEBUG) {error_log( self::$DBConnection->getUserInfo());}
				die( $error );
			}

			// We always, really always want associative arrays
			self::$DBConnection->setFetchMode(MDB2_FETCHMODE_ASSOC);

			//we need to function module for query pre-procesing
			self::$DBConnection->loadModule('Function');
		}

		// we are done. great!
		return true;
	}

	/**
	 * @brief SQL query
	 * @param $query Query string
	 * @returns result as MDB2_Result
	 *
	 * SQL query via MDB2 query()
	 */
	static public function query( $query ){
		// Optimize the query
		$query = self::processQuery( $query );

		self::connect();
		//fix differences between sql versions

		// return the result
		$result = self::$DBConnection->exec( $query );

		// Die if we have an error (error means: bad query, not 0 results!)
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$query.'<br />';
			if(defined("DEBUG") && DEBUG) {error_log( $entry );}
			die( $entry );
		}

		return $result;
	}

	/**
	 * @brief Prepare a SQL query
	 * @param $query Query string
	 * @returns prepared SQL query
	 *
	 * SQL query via MDB2 prepare(), needs to be execute()'d!
	 */
	static public function prepare( $query ){
		// Optimize the query
		$query = self::processQuery( $query );

		self::connect();
		// return the result
		$result = self::$DBConnection->prepare( $query );

		// Die if we have an error (error means: bad query, not 0 results!)
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$query.'<br />';
			if(defined("DEBUG") && DEBUG) {error_log( $entry );}
			die( $entry );
		}

		return $result;
	}

	/**
	 * @brief gets last value of autoincrement
	 * @returns id
	 *
	 * MDB2 lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid(){
		self::connect();
		return self::$DBConnection->lastInsertID();
	}

	/**
	 * @brief Disconnect
	 * @returns true/false
	 *
	 * This is good bye, good bye, yeah!
	 */
	public static function disconnect(){
		// Cut connection if required
		if(self::$DBConnection){
			self::$DBConnection->disconnect();
			self::$DBConnection=false;
		}

		return true;
	}

	/**
	 * @brief Escapes bad characters
	 * @param $string string with dangerous characters
	 * @returns escaped string
	 *
	 * MDB2 escape()
	 */
	public static function escape( $string ){
		self::connect();
		return self::$DBConnection->escape( $string );
	}

	/**
	 * @brief saves database scheme to xml file
	 * @param $file name of file
	 * @returns true/false
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file ){
		self::connectScheme();

		// write the scheme
		$definition = self::$schema->getDefinitionFromDatabase();
		$dump_options = array(
			'output_mode' => 'file',
			'output' => $file,
			'end_of_line' => "\n"
		);
		self::$schema->dumpDatabase( $definition, $dump_options, MDB2_SCHEMA_DUMP_STRUCTURE );

		return true;
	}

	/**
	 * @brief Creates tables from XML file
	 * @param $file file to read structure from
	 * @returns true/false
	 *
	 * TODO: write more documentation
	 */
	public static function createDbFromStructure( $file ){
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );
		
		// Make changes and save them to a temporary file
		$file2 = tempnam( sys_get_temp_dir(), 'oc_db_scheme_' );
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		if( $CONFIG_DBTYPE == 'pgsql' ){ //mysql support it too but sqlite don't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>', '<default>CURRENT_TIMESTAMP</default>', $content );
		}
		file_put_contents( $file2, $content );

		// Try to create tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );
		
		// Delete our temporary file
		unlink( $file2 );

		// Die in case something went wrong
		if( $definition instanceof MDB2_Schema_Error ){
			die( $definition->getMessage().': '.$definition->getUserInfo());
		}
// 		if(OC_Config::getValue('dbtype','sqlite')=='sqlite'){
// 			$definition['overwrite']=true;//always overwrite for sqlite
// 		}
		$ret=self::$schema->createDatabase( $definition );

		// Die in case something went wrong
		if( $ret instanceof MDB2_Error ){
			die ($ret->getMessage() . ': ' . $ret->getUserInfo());
		}

		return true;
	}

	/**
	 * @brief connects to a MDB2 database scheme
	 * @returns true/false
	 *
	 * Connects to a MDB2 database scheme
	 */
	private static function connectScheme(){
		// We need a database connection
		self::connect();

		// Connect if this did not happen before
		if(!self::$schema){
			require_once('MDB2/Schema.php');
			self::$schema=MDB2_Schema::factory(self::$DBConnection);
		}

		return true;
	}

	/**
	 * @brief does minor chages to query
	 * @param $query Query string
	 * @returns corrected query string
	 *
	 * This function replaces *PREFIX* with the value of $CONFIG_DBTABLEPREFIX
	 * and replaces the ` woth ' or " according to the database driver.
	 */
	private static function processQuery( $query ){
		self::connect();
		// We need Database type and table prefix
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		
		// differences is getting the current timestamp
		$query = str_replace( 'NOW()', self::$DBConnection->now(), $query );
		$query = str_replace( 'now()', self::$DBConnection->now(), $query );
		
		// differences in escaping of table names (` for mysql)
		// Problem: what if there is a ` in the value we want to insert?
		if( $CONFIG_DBTYPE == 'sqlite' ){
			$query = str_replace( '`', '\'', $query );
		}
		elseif( $CONFIG_DBTYPE == 'pgsql' ){
			$query = str_replace( '`', '"', $query );
		}

		// replace table name prefix
		$query = str_replace( '*PREFIX*', $CONFIG_DBTABLEPREFIX, $query );

		return $query;
	}
	
	/**
	 * @brief drop a table
	 * @param string $tableNamme the table to drop
	 */
	public static function dropTable($tableName){
		self::connect();
		self::$DBConnection->loadModule('Manager');
		self::$DBConnection->dropTable($tableName);
	}
	
	/**
	 * remove all tables defined in a database structure xml file
	 * @param string $file the xml file describing the tables
	 */
	public static function removeDBStructure($file){
		$CONFIG_DBNAME  = OC_Config::getValue( "dbname", "owncloud" );
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to a temporary file
		$file2 = tempnam( sys_get_temp_dir(), 'oc_db_scheme_' );
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		file_put_contents( $file2, $content );

		// get the tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );
		
		// Delete our temporary file
		unlink( $file2 );
		foreach($definition['tables'] as $name=>$table){
			self::dropTable($name);
		}
	}
	
	/**
	 * Start a transaction or set a savepoint.
	 * @param string $savePoint (optional) name of the savepoint to set
	 */
	public static function beginTransaction($savePoint=''){
		self::connect();
		if (!self::$DBConnection->supports('transactions')) {
			return false;
		}
		if($savePoint && !self::$DBConnection->supports('savepoints')){
			return false;
		}
		if($savePoint){
			self::$DBConnection->beginTransaction($savePoint);
		}else{
			self::$DBConnection->beginTransaction();
		}
	}

	/**
	 * Commit the database changes done during a transaction that is in progress or release a savepoint.
	 * @param string $savePoint (optional) name of the savepoint to commit
	 */
	public static function commit($savePoint=''){
		self::connect();
		if(!self::$DBConnection->inTransaction()){
			return false;
		}
		if($savePoint){
			self::$DBConnection->commit($savePoint);
		}else{
			self::$DBConnection->commit();
		}
	}
}
