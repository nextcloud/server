<?php
/**
 * ownCloud
 *
 * @author Tom Needham
 * @copyright 2012 Tom Needham tom@owncloud.com
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
 * provides an interface to all search providers
 */
class OC_Migrate{
	
	static private $MDB2=false;
	static private $providers=array();
	static private $schema=false;
	static private $uid=false;
	
	/**
	 * register a new migration provider
	 * @param OC_Migrate_Provider $provider
	 */
	public static function registerProvider($provider){
		self::$providers[]=$provider;
	}
	
	/**
	 * @breif creates a migration.db in the users data dir with their app data in
	 * @param @uid string userid of the user to export for
	 * @return bool whether operation was successfull
	 */
	public static function export( $uid ){

		// Only export database users, otherwise we get chaos
		if(!OC_User_Database::userExists( $uid )){
			return false;	
		}
		
		self::$uid = $uid;
		
		if(empty(self::$uid)){
			OC_Log::write('migration','Invalid uid passed',OC_Log::FATAL);
			return false;
			exit();	
		}
		
		self::connectDB();
		$ok = true;
		$return = array();
		
		// Foreach provider
		foreach( self::$providers as $provider ){
			// Check for database.xml
			if(file_exists(OC::$SERVERROOT.'/apps/'.$provider->id.'/appinfo/database.xml')){
				$ok = self::createAppTables( $provider->id );						
			}
			if($ok){
				// Run the export function provided by the providor
				$return[$provider->id]['success'] = $provider->export( $uid );
			} else {
				// Log the error
				OC_Log::write('migration','failed to create migration tables for: '.$provider->id,OC_Log::INFO);
				$return[$provider->id]['success'] = 'false';	
				$return[$provider->id]['message'] = 'failed to create the app tables';
			}
		}
		
		return $return;
		
	}
	
	/**
	* @breif imports a new user
	* @param $uid optional uid to use
	* @return bool if the import succedded
	*/
	public static function import( $uid=false ){
		
		self::$uid = $uid;
		
		if(!self::$uid){
			OC_Log::write('migration','Tried to import without passing a uid',OC_Log::FATAL);
			return false;
			exit();	
		}
		
		// Connect to the db
		if(!self::connectDB()){
			return false;	
		}
		
		// Create the user
		if(!self::createUser($uid, $hash)){
			return false;	
		}
		
		// Now get the list of apps to import from migration.db
		// Then check for migrate.php for these apps
		// If present, run the import function for them.
		
		return true;
	
	}
	
	// @breif connects to migration.db, or creates if not found
	// @return bool whether the operation was successful
	private static function connectDB(){
		OC_Log::write('migration','connecting to migration.db for user: '.self::$uid,OC_Log::INFO);
		// Fail if no user is set
		if(!self::$uid){
			OC_Log::write('migration','connectDB() called without self::$uid being set',OC_Log::INFO);
			return false;
		}
		// Already connected
		if(!self::$MDB2){
			require_once('MDB2.php');
			
			$datadir = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );
			
			// Prepare options array
			$options = array(
				'portability' => MDB2_PORTABILITY_ALL & (!MDB2_PORTABILITY_FIX_CASE),
				'log_line_break' => '<br>',
				'idxname_format' => '%s',
				'debug' => true,
				'quote_identifier' => true
				);
			$dsn = array(
				'phptype'  => 'sqlite3',
				'database' => $datadir.'/'.self::$uid.'/migration.db',
				'mode' => '0644'
			);
			
			// Try to establish connection
			self::$MDB2 = MDB2::factory( $dsn, $options );
			// Die if we could not connect
			if( PEAR::isError( self::$MDB2 )){
				die(self::$MDB2->getMessage());
				OC_Log::write('migration', 'Failed to create/connect to migration.db',OC_Log::FATAL);
				OC_Log::write('migration',self::$MDB2->getUserInfo(),OC_Log::FATAL);
				OC_Log::write('migration',self::$MDB2->getMessage(),OC_Log::FATAL);
				return false;
			} else {
			}
			// We always, really always want associative arrays
			self::$MDB2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		}
		return true;
		
	}
	
	// @breif prepares the db
	// @param $query the sql query to prepare
	public static function prepare( $query ){
		
		// Optimize the query
		$query = self::processQuery( $query );
		
		// Optimize the query
		$query = self::$MDB2->prepare( $query );
		
		// Die if we have an error (error means: bad query, not 0 results!)
		if( PEAR::isError( $query )) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$query.'<br />';
			OC_Log::write('migration',$entry,OC_Log::FATAL);
			return false;
		} else {
			return $query;	
		}
		
	}
	
	// @breif processes the db query
	// @param $query the query to process
	// @return string of processed query
	private static function processQuery( $query ){
		
		self::connectDB();
		$prefix = '';
		
		$query = str_replace( '`', '\'', $query );
		$query = str_replace( 'NOW()', 'datetime(\'now\')', $query );
		$query = str_replace( 'now()', 'datetime(\'now\')', $query );

		// replace table name prefix
		$query = str_replace( '*PREFIX*', $prefix, $query );

		return $query;
		
	}
	
	// @brief copys rows to migration.db from the main database
	// @param $options array of options.
	// @return bool
	public static function copyRows( $options ){
		if( !array_key_exists( 'table', $options ) ){
			return false;	
		}
		
		$return = array();
					
		// Need to include 'where' in the query?
		if( array_key_exists( 'matchval', $options ) && array_key_exists( 'matchcol', $options ) ){
			
			// If only one matchval, create an array
			if(!is_array($options['matchval'])){
				$options['matchval'] = array( $options['matchval'] );	
			}
			
			foreach( $options['matchval'] as $matchval ){
				// Run the query for this match value (where x = y value)
				$query = OC_DB::prepare( "SELECT * FROM *PREFIX*" . $options['table'] . " WHERE " . $options['matchcol'] . " LIKE ?" );
				$results = $query->execute( array( $matchval ) );
				$newreturns = self::insertData( $results, $options );
				$return = array_merge( $return, $newreturns );
			}

		} else {
			// Just get everything
			$query = OC_DB::prepare( "SELECT * FROM *PREFIX*" . $options['table'] );
			$results = $query->execute();
			$return = self::insertData( $results, $options );
	
		}
		
		return $return;
		
	}
	
	// @breif saves a sql data set into migration.db
	// @param $data a sql data set returned from self::prepare()->query()
	// @param $options array of copyRows options
	// @return void
	private static function insertData( $data, $options ){
		$return = array();
		while( $row = $data->fetchRow() ){
			// Now save all this to the migration.db
			$fields = array();
			$values = array();
			foreach($row as $field=>$value){
				$fields[] = $field;
				$values[] = $value;
			}
			
			// Generate some sql
			$sql = "INSERT INTO `" . $options['table'] . '` ( `';
			$fieldssql = implode( '`, `', $fields );
			$sql .= $fieldssql . "` ) VALUES( ";
			$valuessql = substr( str_repeat( '?, ', count( $fields ) ),0,-2 );
			$sql .= $valuessql . " )";
			// Make the query
			$query = self::prepare( $sql );
			if(!$query){
				OC_Log::write('migration','Invalid sql produced: '.$sql,OC_Log::FATAL);	
				return false;
				exit();
			} else {
				$query->execute( $values );
				// Do we need to return some values?
				if( array_key_exists( 'idcol', $options ) ){
					// Yes we do
					$return[] = $row[$options['idcol']];	
				} else {
					// Take a guess and return the first field :)
					$return[] = reset($row);	
				}
			}
		}
		return $return;
	}
	
	// @breif creates the tables in migration.db from an apps database.xml
	// @param $appid string id of the app
	// @return bool whether the operation was successful
	private static function createAppTables( $appid ){
		$file = OC::$SERVERROOT.'/apps/'.$appid.'/appinfo/database.xml';
		if(file_exists( $file )){
			
			if(!self::connectScheme()){
				return false;	
			}
			
			// There is a database.xml file			
			$content = file_get_contents( $file );
			
			$file2 = 'static://db_scheme';
			$content = str_replace( '*dbname*', self::$uid.'/migration', $content );
			$content = str_replace( '*dbprefix*', '', $content );
			
			file_put_contents( $file2, $content );
			
			// Try to create tables
			$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );
	
			unlink( $file2 );
			
			// Die in case something went wrong
			if( $definition instanceof MDB2_Schema_Error ){
				OC_Log::write('migration','Failed to parse database.xml for: '.$appid,OC_Log::FATAL);
				OC_Log::write('migration',$definition->getMessage().': '.$definition->getUserInfo(),OC_Log::FATAL);
				return false;
			}
			
			$definition['overwrite'] = true;
			
			$ret = self::$schema->createDatabase( $definition );
			// Die in case something went wrong
			
			if( $ret instanceof MDB2_Error ){
				OC_Log::write('migration','Failed to create tables for: '.$appid,OC_Log::FATAL);
				OC_Log::write('migration',$ret->getMessage().': '.$ret->getUserInfo(),OC_Log::FATAL);
				return false;
			}
			return true;
			
		} else {
			// No database.xml
			return false;	
		}
	}
	
	
		/**
	 * @brief connects to a MDB2 database scheme
	 * @returns true/false
	 *
	 * Connects to a MDB2 database scheme
	 */
	private static function connectScheme(){
		// We need a mdb2 database connection
		self::connectDB();
		self::$MDB2->loadModule( 'Manager' );
		self::$MDB2->loadModule( 'Reverse' );

		// Connect if this did not happen before
		if( !self::$schema ){
			require_once('MDB2/Schema.php');
			self::$schema=MDB2_Schema::factory( self::$MDB2 );
		}

		return true;
	}
	
	// @breif creates a new user in the database
	// @param $uid string user_id of the user to be created
	// @param $hash string hash of the user to be created
	// @return bool result of user creation
	private static function createUser( $uid, $hash ){
		
		// Check if userid exists
		if(OC_User::userExists( $uid )){
			return false;
		}
		
		// Create the user
		$query = OC_DB::prepare( "INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )" );
		$result = $query->execute( array( $uid, $data['hash']));

		return $result ? true : false;
		
	}
	
}
