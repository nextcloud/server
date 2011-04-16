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
set_include_path($SERVERROOT.'/lib'.PATH_SEPARATOR.$SERVERROOT.'/config'.PATH_SEPARATOR.$SERVERROOT.'/3dparty'.PATH_SEPARATOR.get_include_path().PATH_SEPARATOR.$SERVERROOT);

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
require_once('helper.php');
require_once('database.php');
require_once('app.php');
require_once('files.php');
require_once('filesystem.php');
require_once('filestorage.php');
require_once('fileobserver.php');
require_once('log.php');
require_once('config.php');
require_once('user.php');
require_once('group.php');
require_once('ocs.php');
require_once('connect.php');
require_once('remotestorage.php');
require_once('plugin.php');

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
					mkdir( "$CONFIG_BACKUPDIRECTORY/$user/$root", 0755, true );
				}
				$backupStorage=OC_FILESYSTEM::createStorage('local',array('datadir'=>$CONFIG_BACKUPDIRECTORY));
				$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
				$rootStorage->addObserver($backup);
			}
			OC_FILESYSTEM::mount($rootStorage,'/');

			$CONFIG_DATADIRECTORY = "$CONFIG_DATADIRECTORY_ROOT/$user/$root";
			if( !is_dir( $CONFIG_DATADIRECTORY )){
				mkdir( $CONFIG_DATADIRECTORY, 0755, true );
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
	static public function connect( $signalclass, $signalname, $slotclass, $slotname ){
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
	static public function emit( $signalclass, $signalname, $params = array()){
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
