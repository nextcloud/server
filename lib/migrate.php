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
	* @brief finds and loads the providers
	*/
	static private function findProviders(){
		// Find the providers
		$apps = OC_App::getAllApps();

		foreach($apps as $app){
			$path = OC::$SERVERROOT . '/apps/' . $app . '/appinfo/migrate.php';
			if( file_exists( $path ) ){
				include( $path );
			}
		}
	}

	/**
	 * @brief exports a user, or owncloud instance
	 * @param optional $uid string user id of user to export if export type is user, defaults to current
	 * @param ootional $type string type of export, defualts to user
	 * @param otional $path string path to zip output folder
	 * @return false on error, path to zip on success
	 */
	 public static function export( $uid=null, $type='user', $path=null ){
		$datadir = OC_Config::getValue( 'datadirectory' );
	 	// Validate export type
	 	$types = array( 'user', 'instance', 'system', 'userfiles' );
	 	if( !in_array( $type, $types ) ){
	 		OC_Log::write( 'migration', 'Invalid export type', OC_Log::ERROR );
	 		return json_encode( array( array( 'success' => false ) ) );
	 	}
	 	self::$exporttype = $type;
	 	// Userid?
	 	if( self::$exporttype == 'user' ){
	 		// Check user exists
	 		if( !is_null($uid) ){
                                $db = new OC_User_Database;
		 		if( !$db->userExists( $uid ) ){
					OC_Log::write('migration', 'User: '.$uid.' is not in the database and so cannot be exported.', OC_Log::ERROR);
					return json_encode( array( 'success' => false ) );
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
	 				return json_encode( array( 'success' => false ) );
	 			}
	 			self::$zippath = $path . $zipname;
	 		} else {
	 			// Default path
	 			self::$zippath = get_temp_dir() . '/' . $zipname;
	 		}
	 	}
	 	// Create the zip object
	 	if( !self::createZip() ){
	 		return json_encode( array( 'success' => false ) );
	 	}
	 	// Do the export
	 	self::findProviders();
	 	$exportdata = array();
	 	switch( self::$exporttype ){
	 		case 'user':
	 			// Connect to the db
	 			self::$dbpath = $datadir . '/' . self::$uid . '/migration.db';
	 			if( !self::connectDB() ){
	 				return json_encode( array( 'success' => false ) );
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
				$dbfile = tempnam( get_temp_dir(), "owncloud_export_data_" );
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
	 		return json_encode( array( 'success' => false ) );
	 	}
	 	// Add the export info json to the export zip
	 	self::$content->addFromString( $info, 'export_info.json' );
	 	if( !self::$content->finish() ){
	 		return json_encode( array( 'success' => false ) );
	 	}
	 	return json_encode( array( 'success' => true, 'data' => self::$zippath ) );
	 }

	/**
	* @brief imports a user, or owncloud instance
	* @param $path string path to zip
	* @param optional $type type of import (user or instance)
	* @param optional $uid userid of new user
	*/
	public static function import( $path, $type='user', $uid=null ){
		
		$datadir = OC_Config::getValue( 'datadirectory' );
		// Extract the zip
		if( !$extractpath = self::extractZip( $path ) ){
			return json_encode( array( 'success' => false ) );
		}
		// Get export_info.json
		$scan = scandir( $extractpath );
		// Check for export_info.json
		if( !in_array( 'export_info.json', $scan ) ){
			OC_Log::write( 'migration', 'Invalid import file, export_info.json note found', OC_Log::ERROR );
			return json_encode( array( 'success' => false ) );
		}
		$json = json_decode( file_get_contents( $extractpath . 'export_info.json' ) );
		if( $json->exporttype != $type ){
			OC_Log::write( 'migration', 'Invalid import file', OC_Log::ERROR );
			return json_encode( array( 'success' => false ) );
		}
		self::$exporttype = $type;

		$currentuser = OC_User::getUser();

		// Have we got a user if type is user
		if( self::$exporttype == 'user' ){
			self::$uid = !is_null($uid) ? $uid : $currentuser;
		}
		
		// We need to be an admin if we are not importing our own data
		if(($type == 'user' && self::$uid != $currentuser) || $type != 'user' ){
			if( !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
				// Naughty.
				OC_Log::write( 'migration', 'Import not permitted.', OC_Log::ERROR );
				return json_encode( array( 'success' => false ) ); 	
			}
		}

		// Handle export types
		switch( self::$exporttype ){
			case 'user':
				// Check user availability
				if( !OC_User::userExists( self::$uid ) ){
					OC_Log::write( 'migration', 'User doesn\'t exist', OC_Log::ERROR );
					return json_encode( array( 'success' => false ) );
				}
				// Copy data
				if( !self::copy_r( $extractpath . $json->exporteduser, $datadir . '/' . self::$uid ) ){
					return json_encode( array( 'success' => false ) );
				}
				// Import user app data
				if( !$appsimported = self::importAppData( $extractpath . $json->exporteduser . '/migration.db', $json, self::$uid ) ){
					return json_encode( array( 'success' => false ) );
				}
				// All done!
				if( !self::unlink_r( $extractpath ) ){
					OC_Log::write( 'migration', 'Failed to delete the extracted zip', OC_Log::ERROR );
				}
				return json_encode( array( 'success' => true, 'data' => $appsimported ) );
			break;
			case 'instance':
					/*
					 * EXPERIMENTAL
					// Check for new data dir and dbexport before doing anything
					// TODO

					// Delete current data folder.
					OC_Log::write( 'migration', "Deleting current data dir", OC_Log::INFO );
					if( !self::unlink_r( $datadir, false ) ){
						OC_Log::write( 'migration', 'Failed to delete the current data dir', OC_Log::ERROR );
						return json_encode( array( 'success' => false ) );
					}

					// Copy over data
					if( !self::copy_r( $extractpath . 'userdata', $datadir ) ){
						OC_Log::write( 'migration', 'Failed to copy over data directory', OC_Log::ERROR );
						return json_encode( array( 'success' => false ) );
					}

					// Import the db
					if( !OC_DB::replaceDB( $extractpath . 'dbexport.xml' ) ){
						return json_encode( array( 'success' => false ) );
					}
					// Done
					return json_encode( 'success' => true );
					*/
			break;
		}

	}

	/**
	* @brief recursively deletes a directory
	* @param $dir string path of dir to delete
	* $param optional $deleteRootToo bool delete the root directory
	* @return bool
	*/
	private static function unlink_r( $dir, $deleteRootToo=true ){
		if( !$dh = @opendir( $dir ) ){
			return false;
		}
		while (false !== ($obj = readdir($dh))){
			if($obj == '.' || $obj == '..') {
				continue;
			}
			if (!@unlink($dir . '/' . $obj)){
				self::unlink_r($dir.'/'.$obj, true);
			}
		}
		closedir($dh);
		if ( $deleteRootToo ) {
			@rmdir($dir);
		}
		return true;
	}

	/**
	* @brief copies recursively
	* @param $path string path to source folder
	* @param $dest string path to destination
	* @return bool
	*/
	private static function copy_r( $path, $dest ){
		if( is_dir($path) ){
			@mkdir( $dest );
			$objects = scandir( $path );
			if( sizeof( $objects ) > 0 ){
				foreach( $objects as $file ){
					if( $file == "." || $file == ".." || $file == ".htaccess")
					continue;
					// go on
					if( is_dir( $path . '/' . $file ) ){
						self::copy_r( $path  .'/' . $file, $dest . '/' . $file );
					} else {
						copy( $path . '/' . $file, $dest . '/' . $file );
					}
				}
			}
			return true;
		}
		elseif( is_file( $path ) ){
			return copy( $path, $dest );
		} else {
			return false;
		}
	}

	/**
	* @brief tries to extract the import zip
	* @param $path string path to the zip
	* @return string path to extract location (with a trailing slash) or false on failure
	*/
	static private function extractZip( $path ){
		self::$zip = new ZipArchive;
		// Validate path
	 	if( !file_exists( $path ) ){
	 		OC_Log::write( 'migration', 'Zip not found', OC_Log::ERROR );
	 		return false;
	 	}
		if ( self::$zip->open( $path ) != TRUE ) {
			OC_Log::write( 'migration', "Failed to open zip file", OC_Log::ERROR );
			return false;
		}
		$to = get_temp_dir() . '/oc_import_' . self::$exporttype . '_' . date("y-m-d_H-i-s") . '/';
		if( !self::$zip->extractTo( $to ) ){
			return false;
		}
		self::$zip->close();
		return $to;
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
	 * @brief creates a migration.db in the users data dir with their app data in
	 * @return bool whether operation was successfull
	 */
	private static function exportAppData( ){

		$success = true;
		$return = array();

		// Foreach provider
		foreach( self::$providers as $provider ){
			// Check if the app is enabled
			if( OC_App::isEnabled( $provider->getID() ) ){
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
				$return['apps'][$provider->getID()]['version'] = OC_App::getAppVersion($provider->getID());
			}
		}

		return $return;

	}


	/**
	 * @brief generates json containing export info, and merges any data supplied
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
			$query = OC_DB::prepare( "SELECT password FROM *PREFIX*users WHERE uid = ?" );
			$result = $query->execute( array( self::$uid ) );
			$row = $result->fetchRow();
			$hash = $row ? $row['password'] : false;
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
		return $json;
	}

	/**
	 * @brief connects to migration.db, or creates if not found
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

			// DB type
			if( class_exists( 'SQLite3' ) ){
				$dbtype = 'sqlite3';
			} else if( is_callable( 'sqlite_open' ) ){
				$dbtype = 'sqlite';
			} else {
				OC_Log::write( 'migration', 'SQLite not found', OC_Log::ERROR );
				return false;
			}

			// Prepare options array
			$options = array(
				'portability' => MDB2_PORTABILITY_ALL & (!MDB2_PORTABILITY_FIX_CASE),
				'log_line_break' => '<br>',
				'idxname_format' => '%s',
				'debug' => true,
				'quote_identifier' => true
				);
			$dsn = array(
				'phptype'  => $dbtype,
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
	 * @brief creates the tables in migration.db from an apps database.xml
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
	* @brief tries to create the zip
	* @param $path string path to zip destination
	* @return bool
	*/
	static private function createZip(){
		self::$zip = new ZipArchive;
		// Check if properties are set
		if( !self::$zippath ){
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
	* @brief returns an array of apps that support migration
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
	* @brief imports a new user
	* @param $db string path to migration.db
	* @param $info object of migration info
	* @param $uid optional uid to use
	* @return array of apps with import statuses, or false on failure.
	*/
	public static function importAppData( $db, $info, $uid=null ){
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
				// Is the app installed
				if( !OC_App::isEnabled( $id ) ){
					OC_Log::write( 'migration', 'App: ' . $id . ' is not installed, can\'t import data.', OC_Log::INFO );
					$appsstatus[$id] = 'notsupported';
				} else {
					// Did it succeed on export?
					if( $info->apps->$id->success ){
						// Give the provider the content object
						if( !self::connectDB( $db ) ){
							return false;
						}
						$content = new OC_Migration_Content( self::$zip, self::$MDB2 );
						$provider->setData( self::$uid, $content, $info );
						// Then do the import
						if( !$appsstatus[$id] = $provider->import( $info->apps->$id, $importinfo ) ){
							// Failed to import app
							OC_Log::write( 'migration', 'Failed to import app data for user: ' . self::$uid . ' for app: ' . $id, OC_Log::ERROR );
						}
					} else {
						// Add to failed list
						$appsstatus[$id] = false;
					}
				}
			}
		}

		return $appsstatus;

	}

	/*
	* @brief creates a new user in the database
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
