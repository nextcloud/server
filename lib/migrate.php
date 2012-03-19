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
 * provides an interface to migrate users and whole ownclouds
 */
class OC_Migrate{
	
	
	// Array of OC_Migration_Provider objects
	static private $providers=array();
	// User id of the user to import/export
	static private $uid=false;
	// Holds the ZipArchive object
	static private $zip=false;
	// Stores the type of export
	static private $exporttype=false;
	// Array of temp files to be deleted after zip creation
	static private $tmpfiles=array();
	// Holds the db object
	static private $MDB2=false;
	// Schema db object
	static private $schema=false;
	// Path to the sqlite db
	static private $dbpath=false;
	// Holds the path to the zip file
	static private $zippath=false;
	// Holds the OC_Migration_Content object
	static private $content=false;
	
	/**
	 * register a new migration provider
	 * @param OC_Migrate_Provider $provider
	 */
	public static function registerProvider($provider){
		self::$providers[]=$provider;
	}
	
	/** 
	* @breif finds and loads the providers
	*/
	static private function findProviders(){
		// Find the providers
		$apps = OC_App::getAllApps();
		
		foreach($apps as $app){
			$path = OC::$SERVERROOT . '/apps/' . $app . '/lib/migrate.php';
			if( file_exists( $path ) ){
				include( $path );	
			}	
		}	
	}
	
	/**
	 * @breif exports a user, or owncloud instance
	 * @param ootional $type string type of export, defualts to user
	 * @param otional $path string path to zip output folder
	 * @param optional $uid string user id of user to export if export type is user, defaults to current 
	 */
	 public static function export( $type='user', $path=null, $uid=null ){
		$datadir = OC_Config::getValue( 'datadirectory' );
	 	// Validate export type
	 	$types = array( 'user', 'instance', 'system', 'userfiles' );
	 	if( !in_array( $type, $types ) ){
	 		OC_Log::write( 'migration', 'Invalid export type', OC_Log::ERROR );
	 		return false;	
	 	}
	 	self::$exporttype = $type;
	 	// Userid?
	 	if( self::$exporttype == 'user' ){
	 		// Check user exists
	 		if( !is_null($uid) ){	
		 		if( !OC_User_Database::userExists( $uid ) ){
					OC_Log::write('migration', 'User: '.$uid.' is not in the database and so cannot be exported.', OC_Log::ERROR);
					return false;	
				}
				self::$uid = $uid;
	 		} else {
	 			self::$uid = OC_User::getUser();	
	 		}	
	 	}
	 	// Calculate zipname
	 	if( self::$exporttype == 'user' ){
	 		$zipname = 'oc_export_' . self::$uid . '_' . date("y-m-d_H-i-s") . '.zip';		
	 	} else {
	 		$zipname = 'oc_export_' . self::$exporttype . '_' . date("y-m-d_H-i-s") . '.zip';
	 	}
	 	// Calculate path
	 	if( self::$exporttype == 'user' ){
	 		self::$zippath = $datadir . '/' . self::$uid . '/' . $zipname;	
	 	} else {
	 		if( !is_null( $path ) ){
	 			// Validate custom path
	 			if( !file_exists( $path ) || !is_writeable( $path ) ){
	 				OC_Log::write( 'migration', 'Path supplied is invalid.', OC_Log::ERROR );
	 				return false;	
	 			}
	 			self::$zippath = $path . $zipname;	
	 		} else {
	 			// Default path
	 			self::$zippath = get_temp_dir() . '/' . $zipname;	
	 		}
	 	}
	 	// Create the zip object
	 	self::$zip = new ZipArchive;
	 	if( !self::createZip() ){
	 		return false;	
	 	}
	 	// Do the export
	 	self::findProviders();
	 	$exportdata = array();
	 	switch( self::$exporttype ){
	 		case 'user':
	 			// Connect to the db
	 			self::$dbpath = $datadir . '/' . self::$uid . '/migration.db';
	 			if( !self::connectDB() ){
	 				return false;	
	 			}
	 			self::$content = new OC_Migration_Content( self::$zip, self::$MDB2 );
	 			// Export the app info
			    $exportdata = self::exportAppData();			    
				// Add the data dir to the zip
				self::$content->addDir( $datadir . '/' . self::$uid, true, '/' );
	 		break;	
	 		case 'instance':
	 			self::$content = new OC_Migration_Content( self::$zip );
				// Creates a zip that is compatable with the import function
				$dbfile = tempnam( "/tmp", "owncloud_export_data_" );
				OC_DB::getDbStructure( $dbfile, 'MDB2_SCHEMA_DUMP_ALL');
				
				// Now add in *dbname* and *dbprefix*
				$dbexport = file_get_contents( $dbfile );
				$dbnamestring = "<database>\n\n <name>" . OC_Config::getValue( "dbname", "owncloud" );
				$dbtableprefixstring = "<table>\n\n  <name>" . OC_Config::getValue( "dbtableprefix", "oc_" );
				$dbexport = str_replace( $dbnamestring, "<database>\n\n <name>*dbname*", $dbexport );
				$dbexport = str_replace( $dbtableprefixstring, "<table>\n\n  <name>*dbprefix*", $dbexport );
				// Add the export to the zip
				self::$content->addFromString( $dbexport, "dbexport.xml" );
				// Add user data
				foreach(OC_User::getUsers() as $user){
					self::$content->addDir( $datadir . '/' . $user . '/', true, "/userdata/" );	
				}
			break;
			case 'userfiles':
				self::$content = new OC_Migration_Content( self::$zip );
				// Creates a zip with all of the users files
				foreach(OC_User::getUsers() as $user){
					self::$content->addDir( $datadir . '/' . $user . '/', true, "/" );	
				}
			break;
			case 'system':
				self::$content = new OC_Migration_Content( self::$zip );
				// Creates a zip with the owncloud system files
				self::$content->addDir( OC::$SERVERROOT . '/', false, '/');
				foreach (array(".git", "3rdparty", "apps", "core", "files", "l10n", "lib", "ocs", "search", "settings", "tests") as $dir) {
			    	self::$content->addDir( OC::$SERVERROOT . '/' . $dir, true, "/");
				}
			break;
	 	}
	 	if( !$info = self::getExportInfo( $exportdata ) ){
	 		return false;	
	 	}
	 	// Add the export info json to the export zip
	 	self::$content->addFromString( $info, 'export_info.json' );
	 	if( !self::$content->finish() ){
	 		return false;	
	 	}
	 	return self::$zippath;
	 }
	 
	/**
	 * @brief connects to a MDB2 database scheme
	 * @returns bool
	 */
	static private function connectScheme(){
		// We need a mdb2 database connection
		self::$MDB2->loadModule( 'Manager' );
		self::$MDB2->loadModule( 'Reverse' );

		// Connect if this did not happen before
		if( !self::$schema ){
			require_once('MDB2/Schema.php');
			self::$schema=MDB2_Schema::factory( self::$MDB2 );
		}

		return true;
	}
	
	/**
	 * @breif creates a migration.db in the users data dir with their app data in
	 * @return bool whether operation was successfull
	 */
	private static function exportAppData( ){
				
		$success = true;
		$return = array();
				
		// Foreach provider
		foreach( self::$providers as $provider ){
			$success = true;
			// Does this app use the database?
			if( file_exists( OC::$SERVERROOT.'/apps/'.$provider->getID().'/appinfo/database.xml' ) ){
				// Create some app tables
				$tables = self::createAppTables( $provider->getID() );
				if( is_array( $tables ) ){
					// Save the table names
					foreach($tables as $table){
						$return['apps'][$provider->getID()]['tables'][] = $table;	
					}	
				} else {
					// It failed to create the tables
					$success = false;
				}	
			}
			
			// Run the export function?
			if( $success ){
				// Set the provider properties
				$provider->setData( self::$uid, self::$content );
				$return['apps'][$provider->getID()]['success'] = $provider->export();	
			} else {
				$return['apps'][$provider->getID()]['success'] = false;	
				$return['apps'][$provider->getID()]['message'] = 'failed to create the app tables';	
			}
			
			// Now add some app info the the return array
			$appinfo = OC_App::getAppInfo( $provider->getID() );
			$return['apps'][$provider->getID()]['version'] = $appinfo['version'];
			
		}
		
		return $return;
		
	}
	
	
	/**
	 * @breif generates json containing export info, and merges any data supplied
	 * @param optional $array array of data to include in the returned json
	 * @return bool
	 */
	static private function getExportInfo( $array=array() ){
		$info = array(
						'ocversion' => OC_Util::getVersion(),
						'exporttime' => time(),
						'exportedby' => OC_User::getUser(),
						'exporttype' => self::$exporttype
					);
		// Add hash if user export
		if( self::$exporttype == 'user' ){
			$query = OC_DB::prepare( "SELECT password FROM *PREFIX*users WHERE uid LIKE ?" );
			$result = $query->execute( array( self::$uid ) );
			$row = $result->fetchRow();
			$hash = $row ? $row['password'] : false;
			die(var_dump($hash));
			if( !$hash ){
				OC_Log::write( 'migration', 'Failed to get the users password hash', OC_log::ERROR);
				return false;
			}
			$info['hash'] = $hash; 
			$info['exporteduser'] = self::$uid; 	
		}
		if( !is_array( $array ) ){
			OC_Log::write( 'migration', 'Supplied $array was not an array in getExportInfo()', OC_Log::ERROR );	
		}
		// Merge in other data
		$info = array_merge( $info, (array)$array );
		// Create json
		$json = json_encode( $info );
		return true;
	}
	
	/**
	 * @breif connects to migration.db, or creates if not found
	 * @param $db optional path to migration.db, defaults to user data dir
	 * @return bool whether the operation was successful
	 */
	static private function connectDB( $path=null ){
		// Has the dbpath been set?
		self::$dbpath = !is_null( $path ) ? $path : self::$dbpath;
		if( !self::$dbpath ){
			OC_Log::write( 'migration', 'connectDB() was called without dbpath being set', OC_Log::ERROR );
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
				'database' => self::$dbpath,
				'mode' => '0644'
			);

			// Try to establish connection
			self::$MDB2 = MDB2::factory( $dsn, $options );
			// Die if we could not connect
			if( PEAR::isError( self::$MDB2 ) ){
				die( self::$MDB2->getMessage() );
				OC_Log::write( 'migration', 'Failed to create/connect to migration.db', OC_Log::FATAL );
				OC_Log::write( 'migration', self::$MDB2->getUserInfo(), OC_Log::FATAL );
				OC_Log::write( 'migration', self::$MDB2->getMessage(), OC_Log::FATAL );
				return false;
			}
			// We always, really always want associative arrays
			self::$MDB2->setFetchMode(MDB2_FETCHMODE_ASSOC);
		}
		return true;
		
	}
	
	/**
	 * @breif creates the tables in migration.db from an apps database.xml
	 * @param $appid string id of the app
	 * @return bool whether the operation was successful
	 */
	static private function createAppTables( $appid ){
			
		if( !self::connectScheme() ){
			return false;	
		}
		
		// There is a database.xml file			
		$content = file_get_contents( OC::$SERVERROOT . '/apps/' . $appid . '/appinfo/database.xml' );
		
		$file2 = 'static://db_scheme';
		// TODO get the relative path to migration.db from the data dir
		// For now just cheat
		$path = pathinfo( self::$dbpath );
		$content = str_replace( '*dbname*', self::$uid.'/migration', $content );
		$content = str_replace( '*dbprefix*', '', $content );
		
		$xml = new SimpleXMLElement($content);
		foreach($xml->table as $table){
			$tables[] = (string)$table->name;	
		}	
		
		file_put_contents( $file2, $content );
		
		// Try to create tables
		$definition = self::$schema->parseDatabaseDefinitionFile( $file2 );

		unlink( $file2 );
		
		// Die in case something went wrong
		if( $definition instanceof MDB2_Schema_Error ){
			OC_Log::write( 'migration', 'Failed to parse database.xml for: '.$appid, OC_Log::FATAL );
			OC_Log::write( 'migration', $definition->getMessage().': '.$definition->getUserInfo(), OC_Log::FATAL );
			return false;
		}
		
		$definition['overwrite'] = true;
		
		$ret = self::$schema->createDatabase( $definition );
		
		// Die in case something went wrong
		if( $ret instanceof MDB2_Error ){
			OC_Log::write( 'migration', 'Failed to create tables for: '.$appid, OC_Log::FATAL );
			OC_Log::write( 'migration', $ret->getMessage().': '.$ret->getUserInfo(), OC_Log::FATAL );
			return false;
		}
		return $tables;

	}

	/**
	* @breif tries to create the zip
	* @param $path string path to zip destination
	* @return bool
	*/
	static private function createZip(){
		// Check if properties are set
		if( !self::$zip || !self::$zippath ){
			OC_Log::write('migration', 'createZip() called but $zip and/or $zippath have not been set', OC_Log::ERROR);
			return false;	
		}
		if ( self::$zip->open( self::$zippath, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE ) !== TRUE ) {
			OC_Log::write('migration', 'Failed to create the zip with error: '.self::$zip->getStatusString(), OC_Log::ERROR);
			return false;
	    } else {
	    	return true;	
	    }	
	}
	
	/**
	* @breif returns an array of apps that support migration
	* @return array
	*/
	static public function getApps(){
		$allapps = OC_App::getAllApps();
		foreach($allapps as $app){
			$path = OC::$SERVERROOT . '/apps/' . $app . '/lib/migrate.php';
			if( file_exists( $path ) ){
				$supportsmigration[] = $app;
			}	
		}
		return $supportsmigration;	
	}
	
	/**
	* @breif imports a new user
	* @param $db string path to migration.db
	* @param $info array of migration ino
	* @param $uid optional uid to use
	* @return bool if the import succedded
	*/
	public static function importAppData( $db, $info, $uid=null ){
				
		self::$uid = !is_null( $uid ) ? $uid : $info->exporteduser;
		
		// Check if the db exists
		if( file_exists( $db ) ){
			// Connect to the db
			if(!self::connectDB( $db )){
				OC_Log::write('migration','Failed to connect to migration.db',OC_Log::ERROR);
				return false;
			}	
		} else {
			OC_Log::write('migration','Migration.db not found at: '.$db, OC_Log::FATAL );	
			return false;
		}
		
		// Find providers
		self::findProviders();

		// Generate importinfo array
		$importinfo = array( 
							'olduid' => $info->exporteduser,
							'newuid' => self::$uid
							);
							
		foreach( self::$providers as $provider){
			// Is the app in the export?
			$id = $provider->getID();
			if( isset( $info->apps->$id ) ){
				// Did it succeed?
				if( $info->apps->$id->success ){
					// Give the provider the content object
					// TODO PASS THE PATH TO MIGRATION.DB
					if( !self::connectDB() ){
						return false;	
					}
					$content = new OC_Migration_Content( self::$zip, self::$db );
					$provider->setObject( $content );
					// Then do the import
					$provider->import( $info->apps->$id, $importinfo );	
				}	
			}		
		}
		
		return true;
	
	}
	
	/*
	* @breif creates a new user in the database
	* @param $uid string user_id of the user to be created
	* @param $hash string hash of the user to be created
	* @return bool result of user creation
	*/
	public static function createUser( $uid, $hash ){
		
		// Check if userid exists
		if(OC_User::userExists( $uid )){
			return false;
		}
		
		// Create the user
		$query = OC_DB::prepare( "INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )" );
		$result = $query->execute( array( $uid, $hash));
		if( !$result ){
			OC_Log::write('migration', 'Failed to create the new user "'.$uid."");	
		}
		return $result ? true : false;
		
	}

}
