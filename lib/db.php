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
	const BACKEND_PDO=0;
	const BACKEND_MDB2=1;
	
	static private $connection; //the prefered connection to use, either PDO or MDB2
	static private $backend=null;
	static private $MDB2=false;
	static private $PDO=false;
	static private $schema=false;
	static private $affected=0;
	static private $result=false;
	static private $inTransaction=false;
	static private $prefix=null;
	static private $type=null;

	/**
	 * check which backend we should use
	 * @return BACKEND_MDB2 or BACKEND_PDO
	 */
	private static function getDBBackend(){
		$backend=self::BACKEND_MDB2;
		if(class_exists('PDO') && OC_Config::getValue('installed', false)){//check if we can use PDO, else use MDB2 (instalation always needs to be done my mdb2)
			$type = OC_Config::getValue( "dbtype", "sqlite" );
			if($type=='sqlite3') $type='sqlite';
			$drivers=PDO::getAvailableDrivers();
			if(array_search($type,$drivers)!==false){
				$backend=self::BACKEND_PDO;
			}
		}
	}
	
	/**
	 * @brief connects to the database
	 * @returns true if connection can be established or nothing (die())
	 *
	 * Connects to the database as specified in config.php
	 */
	public static function connect($backend=null){
		if(self::$connection){
			return;
		}
		if(is_null($backend)){
			$backend=self::getDBBackend();
		}
		if($backend==self::BACKEND_PDO){
			self::connectPDO();
			self::$connection=self::$PDO;
			self::$backend=self::BACKEND_PDO;
		}else{
			self::connectMDB2();
			self::$connection=self::$MDB2;
			self::$backend=self::BACKEND_MDB2;
		}
	}

	/**
	 * connect to the database using pdo
	 */
	public static function connectPDO(){
		if(self::$connection){
			if(self::$backend==self::BACKEND_MDB2){
				self::disconnect();
			}else{
				return;
			}
		}
		// The global data we need
		$name = OC_Config::getValue( "dbname", "owncloud" );
		$host = OC_Config::getValue( "dbhost", "" );
		$user = OC_Config::getValue( "dbuser", "" );
		$pass = OC_Config::getValue( "dbpassword", "" );
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		if(strpos($host,':')){
			list($host,$port)=explode(':',$host,2);
		}else{
			$port=false;
		}
		$opts = array();
		$datadir=OC_Config::getValue( "datadirectory", OC::$SERVERROOT.'/data' );
		
		// do nothing if the connection already has been established
		if(!self::$PDO){
			// Add the dsn according to the database type
			switch($type){
				case 'sqlite':
					$dsn='sqlite2:'.$datadir.'/'.$name.'.db';
					break;
				case 'sqlite3':
					$dsn='sqlite:'.$datadir.'/'.$name.'.db';
					break;
				case 'mysql':
					if($port){
						$dsn='mysql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='mysql:dbname='.$name.';host='.$host;
					}
					$opts[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
					break;
				case 'pgsql':
					if($port){
						$dsn='pgsql:dbname='.$name.';host='.$host.';port='.$port;
					}else{
						$dsn='pgsql:dbname='.$name.';host='.$host;
					}
					break;
			}
			try{
				self::$PDO=new PDO($dsn,$user,$pass,$opts);
			}catch(PDOException $e){
				echo( '<b>can not connect to database, using '.$type.'. ('.$e->getMessage().')</center>');
				die();
			}
			// We always, really always want associative arrays
			self::$PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
			self::$PDO->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
		}
		return true;
	}
	
	/**
	 * connect to the database using mdb2
	 */
	public static function connectMDB2(){
		if(self::$connection){
			if(self::$backend==self::BACKEND_PDO){
				self::disconnect();
			}else{
				return;
			}
		}
		// The global data we need
		$name = OC_Config::getValue( "dbname", "owncloud" );
		$host = OC_Config::getValue( "dbhost", "" );
		$user = OC_Config::getValue( "dbuser", "" );
		$pass = OC_Config::getValue( "dbpassword", "" );
		$type = OC_Config::getValue( "dbtype", "sqlite" );
		$SERVERROOT=OC::$SERVERROOT;
		$datadir=OC_Config::getValue( "datadirectory", "$SERVERROOT/data" );

		// do nothing if the connection already has been established
		if(!self::$MDB2){
			// Require MDB2.php (not required in the head of the file so we only load it when needed)
			require_once('MDB2.php');

			// Prepare options array
			$options = array(
			  'portability' => MDB2_PORTABILITY_ALL & (!MDB2_PORTABILITY_FIX_CASE),
			  'log_line_break' => '<br>',
			  'idxname_format' => '%s',
			  'debug' => true,
			  'quote_identifier' => true  );

			// Add the dsn according to the database type
			switch($type){
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
			}
			
			// Try to establish connection
			self::$MDB2 = MDB2::factory( $dsn, $options );
			
			// Die if we could not connect
			if( PEAR::isError( self::$MDB2 )){
				echo( '<b>can not connect to database, using '.$type.'. ('.self::$MDB2->getUserInfo().')</center>');
				OC_Log::write('core',self::$MDB2->getUserInfo(),OC_Log::FATAL);
				OC_Log::write('core',self::$MDB2->getMessage(),OC_Log::FATAL);
				die( $error );
			}
			
			// We always, really always want associative arrays
			self::$MDB2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		}
		
		// we are done. great!
		return true;
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
		if(self::$backend==self::BACKEND_MDB2){
			$result = self::$connection->prepare( $query );

			// Die if we have an error (error means: bad query, not 0 results!)
			if( PEAR::isError($result)) {
				$entry = 'DB Error: "'.$result->getMessage().'"<br />';
				$entry .= 'Offending command was: '.$query.'<br />';
				OC_Log::write('core',$entry,OC_Log::FATAL);
				die( $entry );
			}
		}else{
			try{
				$result=self::$connection->prepare($query);
			}catch(PDOException $e){
				$entry = 'DB Error: "'.$e->getMessage().'"<br />';
				$entry .= 'Offending command was: '.$query.'<br />';
				OC_Log::write('core',$entry,OC_Log::FATAL);
				die( $entry );
			}
			$result=new PDOStatementWrapper($result);
		}
		return $result;
	}

	/**
	 * @brief gets last value of autoincrement
	 * @param $table string The optional table name (will replace *PREFIX*) and add sequence suffix
	 * @returns id
	 *
	 * MDB2 lastInsertID()
	 *
	 * Call this method right after the insert command or other functions may
	 * cause trouble!
	 */
	public static function insertid($table=null){
		self::connect();
		if($table !== null){
			$prefix = OC_Config::getValue( "dbtableprefix", "oc_" );
			$suffix = OC_Config::getValue( "dbsequencesuffix", "_id_seq" );
			$table = str_replace( '*PREFIX*', $prefix, $table );
		}
		return self::$connection->lastInsertId($table.$suffix);
	}

	/**
	 * @brief Disconnect
	 * @returns true/false
	 *
	 * This is good bye, good bye, yeah!
	 */
	public static function disconnect(){
		// Cut connection if required
		if(self::$connection){
			if(self::$backend==self::BACKEND_MDB2){
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
	 * @param $file name of file
	 * @returns true/false
	 *
	 * TODO: write more documentation
	 */
	public static function getDbStructure( $file ,$mode=MDB2_SCHEMA_DUMP_STRUCTURE){
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
		
		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		if( $CONFIG_DBTYPE == 'pgsql' ){ //mysql support it too but sqlite doesn't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>', '<default>CURRENT_TIMESTAMP</default>', $content );
		}
		file_put_contents( $file2, $content );

		// Try to create tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );
		
		//clean up memory
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
	 * @brief update the database scheme
	 * @param $file file to read structure from
	 */
	public static function updateDbFromStructure($file){
		$CONFIG_DBTABLEPREFIX = OC_Config::getValue( "dbtableprefix", "oc_" );
		$CONFIG_DBTYPE = OC_Config::getValue( "dbtype", "sqlite" );

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );
		
		$previousSchema = self::$schema->getDefinitionFromDatabase();
		if (PEAR::isError($previousSchema)) {
			$error = $previousSchema->getMessage();
			OC_Log::write('core','Failed to get existing database structure for upgrading ('.$error.')',OC_Log::FATAL);
			return false;
		}

		// Make changes and save them to an in-memory file
		$file2 = 'static://db_scheme';
		$content = str_replace( '*dbname*', $previousSchema['name'], $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		if( $CONFIG_DBTYPE == 'pgsql' ){ //mysql support it too but sqlite doesn't
			$content = str_replace( '<default>0000-00-00 00:00:00</default>', '<default>CURRENT_TIMESTAMP</default>', $content );
		}
		file_put_contents( $file2, $content );
		$op = self::$schema->updateDatabase($file2, $previousSchema, array(), false);
		
		//clean up memory
		unlink( $file2 );
		
		if (PEAR::isError($op)) {
			$error = $op->getMessage();
			$detail = $op->getDebugInfo();
			OC_Log::write('core','Failed to update database structure ('.$error.', '.$detail.')',OC_Log::FATAL);
			return false;
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
		// We need a mdb2 database connection
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->loadModule('Reverse');

		// Connect if this did not happen before
		if(!self::$schema){
			require_once('MDB2/Schema.php');
			self::$schema=MDB2_Schema::factory(self::$MDB2);
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
		if(is_null(self::$type)){
			self::$type=OC_Config::getValue( "dbtype", "sqlite" );
		}
		$type = self::$type;
		if(is_null(self::$prefix)){
			self::$prefix=OC_Config::getValue( "dbtableprefix", "oc_" );
		}
		$prefix = self::$prefix;
		
		// differences in escaping of table names ('`' for mysql) and getting the current timestamp
		if( $type == 'sqlite' || $type == 'sqlite3' ){
			$query = str_replace( '`', '"', $query );
			$query = str_replace( 'NOW()', 'datetime(\'now\')', $query );
			$query = str_replace( 'now()', 'datetime(\'now\')', $query );
		}elseif( $type == 'mysql' ){
			$query = str_replace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
			$query = str_replace( 'now()', 'CURRENT_TIMESTAMP', $query );
		}elseif( $type == 'pgsql' ){
			$query = str_replace( '`', '"', $query );
			$query = str_replace( 'NOW()', 'CURRENT_TIMESTAMP', $query );
			$query = str_replace( 'now()', 'CURRENT_TIMESTAMP', $query );
		}

		// replace table name prefix
		$query = str_replace( '*PREFIX*', $prefix, $query );

		return $query;
	}
	
	/**
	 * @brief drop a table
	 * @param string $tableNamme the table to drop
	 */
	public static function dropTable($tableName){
		self::connectMDB2();
		self::$MDB2->loadModule('Manager');
		self::$MDB2->dropTable($tableName);
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
		$file2 = tempnam( get_temp_dir(), 'oc_db_scheme_' );
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
	 * @brief replaces the owncloud tables with a new set
	 * @param $file string path to the MDB2 xml db export file
	 */
	 public static function replaceDB( $file ){
	 	
	 	$apps = OC_App::getAllApps();
	 	self::beginTransaction();
	 	// Delete the old tables
	 	self::removeDBStructure( OC::$SERVERROOT . '/db_structure.xml' );
	 	
	 	foreach($apps as $app){
	 		$path = OC::$SERVERROOT.'/apps/'.$app.'/appinfo/database.xml';
	 		if(file_exists($path)){
	 			self::removeDBStructure( $path );	
	 		}
	 	}
	 	
	 	// Create new tables
	 	self::createDBFromStructure( $file );
	 	self::commit();
	 	
	 }
	
	/**
	 * Start a transaction
	 */
	public static function beginTransaction(){
		self::connect();
		if (self::$backend==self::BACKEND_MDB2 && !self::$connection->supports('transactions')) {
			return false;
		}
		self::$connection->beginTransaction();
		self::$inTransaction=true;
	}

	/**
	 * Commit the database changes done during a transaction that is in progress
	 */
	public static function commit(){
		self::connect();
		if(!self::$inTransaction){
			return false;
		}
		self::$connection->commit();
		self::$inTransaction=false;
	}

	/**
	 * check if a result is an error, works with MDB2 and PDOException
	 * @param mixed $result
	 * @return bool
	 */
	public static function isError($result){
		if(!$result){
			return true;
		}elseif(self::$backend==self::BACKEND_MDB2 and PEAR::isError($result)){
			return true;
		}else{
			return false;
		}
	}
}

/**
 * small wrapper around PDOStatement to make it behave ,more like an MDB2 Statement
 */
class PDOStatementWrapper{
	private $statement=null;
	private $lastArguments=array();

	public function __construct($statement){
		$this->statement=$statement;
	}
	
	/**
	 * make execute return the result instead of a bool
	 */
	public function execute($input=array()){
		$this->lastArguments=$input;
		if(count($input)>0){
			$result=$this->statement->execute($input);
		}else{
			$result=$this->statement->execute();
		}
		if($result){
			return $this;
		}else{
			return false;
		}
	}
	
	/**
	 * provide numRows
	 */
	public function numRows(){
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
	public function fetchRow(){
		return $this->statement->fetch();
	}
	
	/**
	 * pass all other function directly to the PDOStatement
	 */
	public function __call($name,$arguments){
		return call_user_func_array(array($this->statement,$name),$arguments);
	}
	
	/**
	 * Provide a simple fetchOne.
	 * fetch single column from the next row
	 * @param int $colnum the column number to fetch
	 */
	public function fetchOne($colnum = 0){
		return $this->statement->fetchColumn($colnum);
	}
}

