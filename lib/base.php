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


// set some stuff
//ob_start();
// error_reporting(E_ALL | E_STRICT);
error_reporting( E_ERROR | E_PARSE | E_WARNING ); // MDB2 gives loads of strict error, disabling for now

date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// calculate the documentroot
$SERVERROOT=substr(__FILE__,0,-13);
$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',$SERVERROOT);
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$WEBROOT=substr($_SERVER["SCRIPT_NAME"],0,strlen($_SERVER["SCRIPT_NAME"])-strlen($SUBURI));



if($WEBROOT!='' and $WEBROOT[0]!=='/'){
	$WEBROOT='/'.$WEBROOT;
}

// set the right include path
// set_include_path(get_include_path().PATH_SEPARATOR.$SERVERROOT.PATH_SEPARATOR.$SERVERROOT.'/inc'.PATH_SEPARATOR.$SERVERROOT.'/config');

// define runtime variables - unless this already has been done
if( !isset( $RUNTIME_NOSETUPFS )){
	$RUNTIME_NOSETUPFS = false;
}

// define default config values
$CONFIG_INSTALLED=false;
$CONFIG_DATADIRECTORY=$SERVERROOT.'/data';
$CONFIG_BACKUPDIRECTORY=$SERVERROOT.'/backup';
$CONFIG_HTTPFORCESSL=false;
$CONFIG_ENABLEBACKUP=false;
$CONFIG_DATEFORMAT='j M Y G:i';
$CONFIG_DBNAME='owncloud';
$CONFIG_DBTYPE='sqlite';
$CONFIG_FILESYSTEM=array();

// include the generated configfile
@include_once($SERVERROOT.'/config/config.php');


$CONFIG_DATADIRECTORY_ROOT=$CONFIG_DATADIRECTORY;// store this in a seperate variable so we can change the data directory to jail users.
// redirect to https site if configured
if(isset($CONFIG_HTTPFORCESSL) and $CONFIG_HTTPFORCESSL){
	if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
		$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		header("Location: $url");
		exit;
	}
}

// load core libs
oc_require_once('helper.php');
oc_require_once('app.php');
oc_require_once('files.php');
oc_require_once('filesystem.php');
oc_require_once('filestorage.php');
oc_require_once('fileobserver.php');
oc_require_once('log.php');
oc_require_once('config.php');
oc_require_once('user.php');
oc_require_once('group.php');
oc_require_once('ocs.php');
oc_require_once('connect.php');
oc_require_once('remotestorage.php');
oc_require_once('plugin.php');

OC_PLUGIN::loadPlugins( "" );

if(!isset($CONFIG_BACKEND)){
	$CONFIG_BACKEND='database';
}
OC_USER::setBackend( $CONFIG_BACKEND );
OC_GROUP::setBackend( $CONFIG_BACKEND );

// Set up file system unless forbidden
if( !$RUNTIME_NOSETUPFS ){
	OC_UTIL::setupFS();
}

// Add the stuff we need always
OC_APP::addPersonalMenuEntry( array( "order" => 1000, "href" => OC_HELPER::linkTo( "", "index.php?logout=1" ), "name" => "Logout" ));
OC_UTIL::addScript( "jquery-1.5.min" );
OC_UTIL::addScript( "jquery-ui-1.8.10.custom.min" );
OC_UTIL::addScript( "js" );
OC_UTIL::addStyle( "jquery-ui-1.8.10.custom" );
OC_UTIL::addStyle( "styles" );

// Load Apps
OC_APP::loadApps();

// check if the server is correctly configured for ownCloud
OC_UTIL::checkserver();

/**
 * Class for utility functions
 *
 */
class OC_UTIL {
	public static $scripts=array();
	public static $styles=array();
	private static $fsSetup=false;

	// Can be set up
	public static function setupFS( $user = "", $root = "files" ){// configure the initial filesystem based on the configuration
		if(self::$fsSetup){//setting up the filesystem twice can only lead to trouble
			return false;
		}

		// Global Variables
		global $SERVERROOT;
		global $CONFIG_DATADIRECTORY_ROOT;
		global $CONFIG_DATADIRECTORY;
		global $CONFIG_BACKUPDIRECTORY;
		global $CONFIG_ENABLEBACKUP;
		global $CONFIG_FILESYSTEM;

		// Create root dir
		if(!is_dir($CONFIG_DATADIRECTORY_ROOT)){
			@mkdir($CONFIG_DATADIRECTORY_ROOT) or die("Can't create data directory ($CONFIG_DATADIRECTORY_ROOT), you can usually fix this by setting the owner of '$SERVERROOT' to the user that the web server uses (www-data for debian/ubuntu)");
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_USER::isLoggedIn()){
			$user = $_SESSION['user_id'];
		}

		if( $user != "" ){ //if we aren't logged in, there is no use to set up the filesystem
			//first set up the local "root" storage and the backupstorage if needed
			$rootStorage=OC_FILESYSTEM::createStorage('local',array('datadir'=>$CONFIG_DATADIRECTORY));
			if($CONFIG_ENABLEBACKUP){
				// This creates the Directorys recursively
				if(!is_dir( "$CONFIG_BACKUPDIRECTORY/$user/$root" )){
					mkdir( "$CONFIG_BACKUPDIRECTORY/$user/$root", 0x755, true );
				}
				$backupStorage=OC_FILESYSTEM::createStorage('local',array('datadir'=>$CONFIG_BACKUPDIRECTORY));
				$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
				$rootStorage->addObserver($backup);
			}
			OC_FILESYSTEM::mount($rootStorage,'/');

			$CONFIG_DATADIRECTORY = "$CONFIG_DATADIRECTORY_ROOT/$user/$root";
			if( !is_dir( $CONFIG_DATADIRECTORY )){
				mkdir( $CONFIG_DATADIRECTORY, 0x755, true );
			}

			//set up the other storages according to the system settings
			foreach($CONFIG_FILESYSTEM as $storageConfig){
				if(OC_FILESYSTEM::hasStorageType($storageConfig['type'])){
					$arguments=$storageConfig;
					unset($arguments['type']);
					unset($arguments['mountpoint']);
					$storage=OC_FILESYSTEM::createStorage($storageConfig['type'],$arguments);
					if($storage){
						OC_FILESYSTEM::mount($storage,$storageConfig['mountpoint']);
					}
				}
			}

			//jail the user into his "home" directory
			OC_FILESYSTEM::chroot("/$user/$root");
			self::$fsSetup=true;
		}
	}

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion(){
		return array(1,2,0);
	}

	/**
	 * add a javascript file
	 *
	 * @param url  $url
	 */
	public static function addScript( $application, $file = null ){
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		self::$scripts[] = "$application/js/$file";
	}

	/**
	 * add a css file
	 *
	 * @param url  $url
	 */
	public static function addStyle( $application, $file = null ){
		if( is_null( $file )){
			$file = $application;
			$application = "";
		}
		self::$styles[] = "$application/css/$file";
	}

	/**
	 * check if the current server configuration is suitable for ownCloud
	 *
	 */
	public static function checkServer(){
		global $SERVERROOT;
		global $CONFIG_DATADIRECTORY_ROOT;
		global $CONFIG_BACKUPDIRECTORY;
		global $CONFIG_ENABLEBACKUP;
		global $CONFIG_INSTALLED;
		$error='';
		if(!is_callable('sqlite_open') and !is_callable('mysql_connect')){
			$error.='No database drivers (sqlite or mysql) installed.<br/>';
		}
		global $CONFIG_DBTYPE;
		global $CONFIG_DBNAME;
		if(!stristr(PHP_OS, 'WIN')){
			if($CONFIG_DBTYPE=='sqlite'){
				$file=$SERVERROOT.'/'.$CONFIG_DBNAME;
				if(file_exists($file)){
					$prems=substr(decoct(fileperms($file)),-3);
					if(substr($prems,2,1)!='0'){
						@chmod($file,0660);
						clearstatcache();
						$prems=substr(decoct(fileperms($file)),-3);
						if(substr($prems,2,1)!='0'){
							$error.='SQLite database file ('.$file.') is readable from the web<br/>';
						}
					}
				}
			}
			$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
			if(substr($prems,-1)!='0'){
				chmodr($CONFIG_DATADIRECTORY_ROOT,0770);
				clearstatcache();
				$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
				if(substr($prems,2,1)!='0'){
					$error.='Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') is readable from the web<br/>';
				}
			}
			if($CONFIG_ENABLEBACKUP){
				$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
				if(substr($prems,-1)!='0'){
					chmodr($CONFIG_BACKUPDIRECTORY,0770);
					clearstatcache();
					$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
					if(substr($prems,2,1)!='0'){
						$error.='Data directory ('.$CONFIG_BACKUPDIRECTORY.') is readable from the web<br/>';
					}
				}
			}
		}else{
			//TODO: premisions checks for windows hosts
		}
		if($error){
			die($error);
		}
	}
}

/**
 * This class manages the hooks. It basically provides two functions: adding
 * slots and emitting signals.
 */
class OC_HOOK{
	static private $registered = array();

	/**
	 * @brief connects a function to a hook
	 * @param $signalclass class name of emitter
	 * @param $signalname name of signal
	 * @param $slotclass class name of slot
	 * @param $slotname name of slot
	 * @returns true/false
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	public function connect( $signalclass, $signalname, $slotclass, $slotname ){
		// Cerate the data structure
		if( !array_key_exists( $signalclass, self::$registered )){
			self::$registered[$signalclass] = array();
		}
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )){
			self::$registered[$signalclass][$signalname] = array();
		}

		// register hook
		self::$registered[$signalclass][$signalname][] = array(
		  "class" => $slotclass,
		  "name" => $slotname );

		// No chance for failure ;-)
		return true;
	}

	/**
	 * @brief emitts a signal
	 * @param $signalclass class name of emitter
	 * @param $signalname name of signal
	 * @param $params defautl: array() array with additional data
	 * @returns true if slots exists or false if not
	 *
	 * Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	public function emit( $signalclass, $signalname, $params = array()){
		// Return false if there are no slots
		if( !array_key_exists( $signalclass, self::$registered )){
			return false;
		}
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )){
			return false;
		}

		// Call all slots
		foreach( $registered[$signalclass][$signalname] as $i ){
			call_user_func( array( $i["class"], $i["name"] ), $params );
		}

		// return true
		return true;
	}
}

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
		global $CONFIG_DBNAME;
		global $CONFIG_DBHOST;
		global $CONFIG_DBUSER;
		global $CONFIG_DBPASSWORD;
		global $CONFIG_DBTYPE;
		global $DOCUMENTROOT;
		global $SERVERROOT;

		// do nothing if the connection already has been established
		if(!self::$DBConnection){
			// Require MDB2.php (TODO: why here not in head of file?)
			@oc_require_once('MDB2.php');

			// Prepare options array
			$options = array(
			  'portability' => MDB2_PORTABILITY_ALL,
			  'log_line_break' => '<br>',
			  'idxname_format' => '%s',
			  'debug' => true,
			  'quote_identifier' => true  );

			// Add the dsn according to the database type
			if( $CONFIG_DBTYPE == 'sqlite' ){
				// sqlite
				$dsn = array(
				  'phptype'  => 'sqlite',
				  'database' => "$SERVERROOT/$CONFIG_DBNAME",
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
				error_log( $error);
				error_log( self::$DBConnection->getUserInfo());
				die( $error );
			}

			// We always, really always want associative arrays
			self::$DBConnection->setFetchMode(MDB2_FETCHMODE_ASSOC);
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
			$entry .= 'Offending command was: '.$cmd.'<br />';
			error_log( $entry );
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
		//fix differences between sql versions

		// return the result
		$result = self::$DBConnection->prepare( $query );

		// Die if we have an error (error means: bad query, not 0 results!)
		if( PEAR::isError($result)) {
			$entry = 'DB Error: "'.$result->getMessage().'"<br />';
			$entry .= 'Offending command was: '.$cmd.'<br />';
			error_log( $entry );
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
		global $CONFIG_DBNAME;
		global $CONFIG_DBTABLEPREFIX;

		self::connectScheme();

		// read file
		$content = file_get_contents( $file );

		// Make changes and save them to a temporary file
		$file2 = tempnam( sys_get_temp_dir(), 'oc_db_scheme_' );
		$content = str_replace( '*dbname*', $CONFIG_DBNAME, $content );
		$content = str_replace( '*dbprefix*', $CONFIG_DBTABLEPREFIX, $content );
		file_put_contents( $file2, $content );

		// Try to create tables
		$definition = @self::$schema->parseDatabaseDefinitionFile( $file2 );

		// Delete our temporary file
		unlink( $file2 );

		// Die in case something went wrong
		if( $definition instanceof MDB2_Schema_Error ){
			die( $definition->getMessage().': '.$definition->getUserInfo());
		}
		$ret=@self::$schema->createDatabase( $definition );

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
			@oc_require_once('MDB2/Schema.php');
			self::$schema=&MDB2_Schema::factory(self::$DBConnection);
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
		// We need Database type and table prefix
		global $CONFIG_DBTYPE;
		global $CONFIG_DBTABLEPREFIX;

		// differences in escaping of table names (` for mysql)
		// Problem: what if there is a ` in the value we want to insert?
		if( $CONFIG_DBTYPE == 'sqlite' ){
			$query = str_replace( '`', '\'', $query );
		}
		elseif( $CONFIG_DBTYPE == 'pgsql' ){
			$query = str_replace( '`', '"', $query );
		}

		// replace table names
		$query = str_replace( '*PREFIX*', $CONFIG_DBTABLEPREFIX, $query );

		return $query;
	}
}


//custom require/include functions because not all hosts allow us to set the include path
function oc_require($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;

	if(is_file($file)){
		return require($file);
	}
	elseif(is_file($SERVERROOT.'/'.$file)){
		return require($SERVERROOT.'/'.$file);
	}
	elseif(is_file($SERVERROOT.'/lib/'.$file)){
		return require($SERVERROOT.'/lib/'.$file);
	}
	elseif(is_file($SERVERROOT.'/3dparty/'.$file)){
		return require($SERVERROOT.'/3dparty/'.$file);
	}
}

function oc_require_once($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;

	if(is_file($file)){
		return require_once($file);
	}
	elseif(is_file($SERVERROOT.'/'.$file)){
		return require_once($SERVERROOT.'/'.$file);
	}
	elseif(is_file($SERVERROOT.'/lib/'.$file)){
		return require_once($SERVERROOT.'/lib/'.$file);
	}
	elseif(is_file($SERVERROOT.'/3dparty/'.$file)){
		return require_once($SERVERROOT.'/3dparty/'.$file);
	}
}

function oc_include($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;

	if(is_file($file)){
		return include($file);
	}
	elseif(is_file($SERVERROOT.'/'.$file)){
		return include($SERVERROOT.'/'.$file);
	}
	elseif(is_file($SERVERROOT.'/lib/'.$file)){
		return include($SERVERROOT.'/lib/'.$file);
	}
	elseif(is_file($SERVERROOT.'/3dparty/'.$file)){
		return include($SERVERROOT.'/3dparty/'.$file);
	}
}

function oc_include_once($file){
	global $SERVERROOT;
	global $DOCUMENTROOT;
	global $WEBROOT;
	global $CONFIG_DBNAME;
	global $CONFIG_DBHOST;
	global $CONFIG_DBUSER;
	global $CONFIG_DBPASSWORD;
	global $CONFIG_DBTYPE;
	global $CONFIG_DATADIRECTORY;
	global $CONFIG_HTTPFORCESSL;
	global $CONFIG_DATEFORMAT;
	global $CONFIG_INSTALLED;

	if(is_file($file)){
		return include_once($file);
	}
	elseif(is_file($SERVERROOT.'/'.$file)){
		return include_once($SERVERROOT.'/'.$file);
	}
	elseif(is_file($SERVERROOT.'/lib/'.$file)){
		return include_once($SERVERROOT.'/lib/'.$file);
	}
	elseif(is_file($SERVERROOT.'/3dparty/'.$file)){
		return include_once($SERVERROOT.'/3dparty/'.$file);
	}
}

function chmodr($path, $filemode) {
//	 echo "$path<br/>";
	if (!is_dir($path))
		return chmod($path, $filemode);
	$dh = opendir($path);
	while (($file = readdir($dh)) !== false) {
		if($file != '.' && $file != '..') {
			$fullpath = $path.'/'.$file;
			if(is_link($fullpath))
				return FALSE;
			elseif(!is_dir($fullpath) && !chmod($fullpath, $filemode))
					return FALSE;
			elseif(!chmodr($fullpath, $filemode))
				return FALSE;
		}
	}
	closedir($dh);
	if(chmod($path, $filemode))
		return TRUE;
	else
		return FALSE;
}

?>
