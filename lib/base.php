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
error_reporting(E_ALL | E_STRICT);

date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// calculate the documentroot
$SERVERROOT=substr(__FILE__,0,-13);
$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',$SERVERROOT);
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$scriptName=$_SERVER["SCRIPT_NAME"];
if(substr($scriptName,-1)=='/'){//if the script isn't a file assume index.php
  $scriptName.='index.php';
}
$WEBROOT=substr($scriptName,0,strlen($scriptName)-strlen($SUBURI));



if($WEBROOT!='' and $WEBROOT[0]!=='/'){
	$WEBROOT='/'.$WEBROOT;
}

// set the right include path
set_include_path($SERVERROOT.'/lib'.PATH_SEPARATOR.$SERVERROOT.'/config'.PATH_SEPARATOR.$SERVERROOT.'/3dparty'.PATH_SEPARATOR.get_include_path().PATH_SEPARATOR.$SERVERROOT);

// define runtime variables - unless this already has been done
if( !isset( $RUNTIME_NOSETUPFS )){
	$RUNTIME_NOSETUPFS = false;
}
if( !isset( $RUNTIME_NOAPPS )){
	$RUNTIME_NOAPPS = false;
}

// Doing the config stuff first
require_once('config.php');

// TODO: we should get rid of this one, too
// WARNING: to make everything even more confusing, DATADIRECTORY is a var that
//   changes and DATATIRECTORY_ROOT stays the same, but is set by
//   "datadirectory". Any questions?
$CONFIG_DATADIRECTORY = OC_CONFIG::getValue( "datadirectory", "$SERVERROOT/data" );

// redirect to https site if configured
if( OC_CONFIG::getValue( "forcessl", false )){
	if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
		$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		header("Location: $url");
		exit();
	}
}

// load core libs
require_once('helper.php');
require_once('database.php');
require_once('app.php');
require_once('appconfig.php');
require_once('files.php');
require_once('filesystem.php');
require_once('filestorage.php');
require_once('apps/files_sharing/sharedstorage.php');
require_once('l10n.php');
require_once('preferences.php');
require_once('log.php');
require_once('user.php');
require_once('group.php');
require_once('ocs.php');
require_once('ocsclient.php');
require_once('connect.php');
require_once('remotestorage.php');
require_once('search.php');

$error=(count(OC_UTIL::checkServer())>0);

OC_USER::useBackend( OC_CONFIG::getValue( "userbackend", "database" ));
OC_GROUP::setBackend( OC_CONFIG::getValue( "groupbackend", "database" ));

// Set up file system unless forbidden
if(!$error and !$RUNTIME_NOSETUPFS ){
	OC_UTIL::setupFS();
}

// Add the stuff we need always
OC_UTIL::addScript( "jquery-1.5.min" );
OC_UTIL::addScript( "jquery-ui-1.8.10.custom.min" );
OC_UTIL::addScript( "js" );
OC_UTIL::addStyle( "jquery-ui-1.8.10.custom" );
OC_UTIL::addStyle( "styles" );

// Load Apps
if(!$error and !$RUNTIME_NOAPPS ){
	OC_APP::loadApps();
}

/**
 * Class for utility functions
 *
 */
class OC_UTIL {
	public static $scripts=array();
	public static $styles=array();
	public static $headers=array();
	private static $fsSetup=false;

	// Can be set up
	public static function setupFS( $user = "", $root = "files" ){// configure the initial filesystem based on the configuration
		if(self::$fsSetup){//setting up the filesystem twice can only lead to trouble
			return false;
		}

		// Global Variables
		global $SERVERROOT;
		global $CONFIG_DATADIRECTORY;

		$CONFIG_DATADIRECTORY_ROOT = OC_CONFIG::getValue( "datadirectory", "$SERVERROOT/data" );
		$CONFIG_BACKUPDIRECTORY = OC_CONFIG::getValue( "backupdirectory", "$SERVERROOT/backup" );

		// Create root dir
		if(!is_dir($CONFIG_DATADIRECTORY_ROOT)){
			@mkdir($CONFIG_DATADIRECTORY_ROOT) or die("Can't create data directory ($CONFIG_DATADIRECTORY_ROOT), you can usually fix this by setting the owner of '$SERVERROOT' to the user that the web server uses (www-data for debian/ubuntu)");
		}

		// If we are not forced to load a specific user we load the one that is logged in
		if( $user == "" && OC_USER::isLoggedIn()){
			$user = OC_USER::getUser();
		}

		if( $user != "" ){ //if we aren't logged in, there is no use to set up the filesystem
			//first set up the local "root" storage and the backupstorage if needed
			$rootStorage=OC_FILESYSTEM::createStorage('local',array('datadir'=>$CONFIG_DATADIRECTORY_ROOT));
// 			if( OC_CONFIG::getValue( "enablebackup", false )){
// 				// This creates the Directorys recursively
// 				if(!is_dir( "$CONFIG_BACKUPDIRECTORY/$user/$root" )){
// 					mkdir( "$CONFIG_BACKUPDIRECTORY/$user/$root", 0755, true );
// 				}
// 				$backupStorage=OC_FILESYSTEM::createStorage('local',array('datadir'=>$CONFIG_BACKUPDIRECTORY));
// 				$backup=new OC_FILEOBSERVER_BACKUP(array('storage'=>$backupStorage));
// 				$rootStorage->addObserver($backup);
// 			}
			OC_FILESYSTEM::mount($rootStorage,'/');

			// TODO add this storage provider in a proper way
			$sharedStorage = OC_FILESYSTEM::createStorage('shared',array('datadir'=>'/'.$_SESSION['user_id'].'/files/Share/'));
			OC_FILESYSTEM::mount($sharedStorage,'/'.$_SESSION['user_id'].'/files/Share/');
			
			$CONFIG_DATADIRECTORY = "$CONFIG_DATADIRECTORY_ROOT/$user/$root";
			if( !is_dir( $CONFIG_DATADIRECTORY )){
				mkdir( $CONFIG_DATADIRECTORY, 0755, true );
			}

// TODO: find a cool way for doing this
// 			//set up the other storages according to the system settings
// 			foreach($CONFIG_FILESYSTEM as $storageConfig){
// 				if(OC_FILESYSTEM::hasStorageType($storageConfig['type'])){
// 					$arguments=$storageConfig;
// 					unset($arguments['type']);
// 					unset($arguments['mountpoint']);
// 					$storage=OC_FILESYSTEM::createStorage($storageConfig['type'],$arguments);
// 					if($storage){
// 						OC_FILESYSTEM::mount($storage,$storageConfig['mountpoint']);
// 					}
// 				}
// 			}

			//jail the user into his "home" directory
			OC_FILESYSTEM::chroot("/$user/$root");
			self::$fsSetup=true;
		}
	}

	public static function tearDownFS(){
		OC_FILESYSTEM::tearDown();
		self::$fsSetup=false;
	}

	/**
	 * get the current installed version of ownCloud
	 * @return array
	 */
	public static function getVersion(){
		return array(1,60,0);
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
		if( !empty( $application )){
			self::$scripts[] = "$application/js/$file";
		}else{
			self::$scripts[] = "js/$file";
		}
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
		if( !empty( $application )){
			self::$styles[] = "$application/css/$file";
		}else{
			self::$styles[] = "css/$file";
		}
	}

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attrobutes for the element
	 * @param string $text the text content for the element
	 */
	public static function addHeader( $tag, $attributes, $text=''){
		self::$headers[]=array('tag'=>$tag,'attributes'=>$attributes,'text'=>$text);
	}

       /**
         * formats a timestamp in the "right" way
         *
         * @param int timestamp $timestamp
         * @param bool dateOnly option to ommit time from the result
         */
        public static function formatDate( $timestamp,$dateOnly=false){
			if(isset($_SESSION['timezone'])){//adjust to clients timezone if we know it
				$systemTimeZone = intval(exec('date +%z'));
				$systemTimeZone=(round($systemTimeZone/100,0)*60)+($systemTimeZone%100);
				$clientTimeZone=$_SESSION['timezone']*60;
				$offset=$clientTimeZone-$systemTimeZone;
				$timestamp=$timestamp+$offset*60;
			}
			$timeformat=$dateOnly?'F j, Y':'F j, Y, H:i';
			return date($timeformat,$timestamp);
        }

	/**
	 * Shows a pagenavi widget where you can jump to different pages.
	 *
	 * @param int $pagecount
	 * @param int $page
	 * @param string $url
	 * @return OC_TEMPLATE
	 */
	public static function getPageNavi($pagecount,$page,$url) {

		$pagelinkcount=8;
		if ($pagecount>1) {
			$pagestart=$page-$pagelinkcount;
			if($pagestart<0) $pagestart=0;
			$pagestop=$page+$pagelinkcount;
			if($pagestop>$pagecount) $pagestop=$pagecount;
			
			$tmpl = new OC_TEMPLATE( '', 'part.pagenavi', '' );
			$tmpl->assign('page',$page);
			$tmpl->assign('pagecount',$pagecount);
			$tmpl->assign('pagestart',$pagestart);
			$tmpl->assign('pagestop',$pagestop);
			$tmpl->assign('url',$url);
			return $tmpl;
		}
	}



	/**
	 * check if the current server configuration is suitable for ownCloud
	 * @return array arrays with error messages and hints
	 */
	public static function checkServer(){
		global $SERVERROOT;
		global $CONFIG_DATADIRECTORY;

		$CONFIG_DATADIRECTORY_ROOT = OC_CONFIG::getValue( "datadirectory", "$SERVERROOT/data" );;
		$CONFIG_BACKUPDIRECTORY = OC_CONFIG::getValue( "backupdirectory", "$SERVERROOT/backup" );
		$CONFIG_INSTALLED = OC_CONFIG::getValue( "installed", false );
		$errors=array();

		//check for database drivers
		if(!is_callable('sqlite_open') and !is_callable('mysql_connect')){
			$errors[]=array('error'=>'No database drivers (sqlite or mysql) installed.<br/>','hint'=>'');//TODO: sane hint
		}
		$CONFIG_DBTYPE = OC_CONFIG::getValue( "dbtype", "sqlite" );
		$CONFIG_DBNAME = OC_CONFIG::getValue( "dbname", "owncloud" );
		
		//try to get the username the httpd server runs on, used in hints
		$stat=stat($_SERVER['DOCUMENT_ROOT']);
		if(is_callable('posix_getpwuid')){
			$serverUser=posix_getpwuid($stat['uid']);
			$serverUser='\''.$serverUser['name'].'\'';
		}else{
			$serverUser='\'www-data\' for ubuntu/debian';//TODO: try to detect the distro and give a guess based on that
		}
		
		//common hint for all file permissons error messages
		$permissionsHint="Permissions can usually be fixed by setting the owner of the file or directory to the user the web server runs as ($serverUser)";
		
		//check for correct file permissions
		if(!stristr(PHP_OS, 'WIN')){
			$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
			if(substr($prems,-1)!='0'){
				OC_HELPER::chmodr($CONFIG_DATADIRECTORY_ROOT,0770);
				clearstatcache();
				$prems=substr(decoct(fileperms($CONFIG_DATADIRECTORY_ROOT)),-3);
				if(substr($prems,2,1)!='0'){
					$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') is readable from the web<br/>','hint'=>$permissionsHint);
				}
			}
			if( OC_CONFIG::getValue( "enablebackup", false )){
				$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
				if(substr($prems,-1)!='0'){
					OC_HELPER::chmodr($CONFIG_BACKUPDIRECTORY,0770);
					clearstatcache();
					$prems=substr(decoct(fileperms($CONFIG_BACKUPDIRECTORY)),-3);
					if(substr($prems,2,1)!='0'){
						$errors[]=array('error'=>'Data directory ('.$CONFIG_BACKUPDIRECTORY.') is readable from the web<br/>','hint'=>$permissionsHint);
					}
				}
			}
		}else{
			//TODO: premisions checks for windows hosts
		}
		if(is_dir($CONFIG_DATADIRECTORY_ROOT) and !is_writable($CONFIG_DATADIRECTORY_ROOT)){
			$errors[]=array('error'=>'Data directory ('.$CONFIG_DATADIRECTORY_ROOT.') not writable by ownCloud<br/>','hint'=>$permissionsHint);
		}

		//TODO: check for php modules

		return $errors;
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
		foreach( self::$registered[$signalclass][$signalname] as $i ){
			call_user_func( array( $i["class"], $i["name"] ), $params );
		}

		// return true
		return true;
	}
}
?>
