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
 * Class that is a namespace for all global OC variables
 * No, we can not put this class in its own file because it is used by
 * OC_autoload!
 */
class OC{
	/**
	 * Assoziative array for autoloading. classname => filename
	 */
	public static $CLASSPATH = array();
	/**
	 * $_SERVER['DOCUMENTROOT'] but without symlinks
	 */
	public static $DOCUMENTROOT = '';
	/**
	 * The installation path for owncloud on the server (e.g. /srv/http/owncloud)
	 */
	public static $SERVERROOT = '';
	/**
	 * the current request path relative to the owncloud root (e.g. files/index.php)
	 */
	public static $SUBURI = '';
	/**
	 * the owncloud root path for http requests (e.g. owncloud/)
	 */
	public static $WEBROOT = '';
	/**
	 * the folder that stores that data files for the filesystem of the user (e.g. /srv/http/owncloud/data/myusername/files)
	 */
	public static $CONFIG_DATADIRECTORY = '';
	/**
	 * the folder that stores the data for the root filesystem (e.g. /srv/http/owncloud/data)
	 */
	public static $CONFIG_DATADIRECTORY_ROOT = '';
	/**
	 * The installation path of the 3rdparty folder on the server (e.g. /srv/http/owncloud/3rdparty)
	 */
	public static $THIRDPARTYROOT = '';
	/**
	 * the root path of the 3rdparty folder for http requests (e.g. owncloud/3rdparty)
	 */
	public static $THIRDPARTYWEBROOT = '';
	/**
	 * The installation path of the apps folder on the server (e.g. /srv/http/owncloud)
	 */
	public static $APPSROOT = '';
	/**
	 * the root path of the apps folder for http requests (e.g. owncloud)
	 */
	public static $APPSWEBROOT = '';
	/*
	 * requested app
	 */
	public static $REQUESTEDAPP = '';
	/*
	 * requested file of app
	 */
	public static $REQUESTEDFILE = '';
	/**
	 * SPL autoload
	 */
	public static function autoload($className){
		if(array_key_exists($className,OC::$CLASSPATH)){
			require_once OC::$CLASSPATH[$className];
		}
		elseif(strpos($className,'OC_')===0){
			require_once strtolower(str_replace('_','/',substr($className,3)) . '.php');
		}
		elseif(strpos($className,'OCP\\')===0){
			require_once 'public/'.strtolower(str_replace('\\','/',substr($className,3)) . '.php');
		}
		elseif(strpos($className,'Sabre_')===0) {
			require_once str_replace('_','/',$className) . '.php';
		}
		elseif(strpos($className,'Test_')===0){
			require_once 'tests/lib/'.strtolower(str_replace('_','/',substr($className,5)) . '.php');
		}
	}

	/**
	 * autodetects the formfactor of the used device
	 * default -> the normal desktop browser interface
	 * mobile -> interface for smartphones
	 * tablet -> interface for tablets
	 * standalone -> the default interface but without header, footer and sidebar. just the application. useful to ue just a specific app on the desktop in a standalone window.
	 */
	public static function detectFormfactor(){
		// please add more useragent strings for other devices
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			if(stripos($_SERVER['HTTP_USER_AGENT'],'ipad')>0) {
				$mode='tablet';
			}elseif(stripos($_SERVER['HTTP_USER_AGENT'],'iphone')>0){
				$mode='mobile';
			}elseif((stripos($_SERVER['HTTP_USER_AGENT'],'N9')>0) and (stripos($_SERVER['HTTP_USER_AGENT'],'nokia')>0)){
				$mode='mobile';
			}else{
				$mode='default';
			}
		}else{
			$mode='default';
		}
		return($mode);
	}

	public static function initPaths(){
		// calculate the documentroot
		OC::$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
		OC::$SERVERROOT=str_replace("\\",'/',substr(__FILE__,0,-13));
		OC::$SUBURI= str_replace("\\","/",substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen(OC::$SERVERROOT)));
		$scriptName=$_SERVER["SCRIPT_NAME"];
		if(substr($scriptName,-1)=='/'){
			$scriptName.='index.php';
			//make sure suburi follows the same rules as scriptName
			if(substr(OC::$SUBURI,-9)!='index.php'){
				if(substr(OC::$SUBURI,-1)!='/'){
					OC::$SUBURI=OC::$SUBURI.'/';
				}
				OC::$SUBURI=OC::$SUBURI.'index.php';
			}
		}
                OC::$WEBROOT=substr($scriptName,0,strlen($scriptName)-strlen(OC::$SUBURI));
		// try a new way to detect the WEBROOT which is simpler and also works with the app directory outside the owncloud folder. let´s see if this works for everybody
//		OC::$WEBROOT=substr(OC::$SERVERROOT,strlen(OC::$DOCUMENTROOT));


		if(OC::$WEBROOT!='' and OC::$WEBROOT[0]!=='/'){
			OC::$WEBROOT='/'.OC::$WEBROOT;
		}

		// ensure we can find OC_Config
		set_include_path(
			OC::$SERVERROOT.'/lib'.PATH_SEPARATOR.
			get_include_path()
		);

		// search the 3rdparty folder
		if(OC_Config::getValue('3rdpartyroot', '')<>'' and OC_Config::getValue('3rdpartyurl', '')<>''){
			OC::$THIRDPARTYROOT=OC_Config::getValue('3rdpartyroot', '');
			OC::$THIRDPARTYWEBROOT=OC_Config::getValue('3rdpartyurl', '');
		}elseif(file_exists(OC::$SERVERROOT.'/3rdparty')){
			OC::$THIRDPARTYROOT=OC::$SERVERROOT;
			OC::$THIRDPARTYWEBROOT=OC::$WEBROOT;
		}elseif(file_exists(OC::$SERVERROOT.'/../3rdparty')){
			OC::$THIRDPARTYWEBROOT=rtrim(dirname(OC::$WEBROOT), '/');
			OC::$THIRDPARTYROOT=rtrim(dirname(OC::$SERVERROOT), '/');
		}else{
			echo("3rdparty directory not found! Please put the ownCloud 3rdparty folder in the ownCloud folder or the folder above. You can also configure the location in the config.php file.");
			exit;
		}

		// search the apps folder
		if(OC_Config::getValue('appsroot', '')<>''){
			OC::$APPSROOT=OC_Config::getValue('appsroot', '');
			OC::$APPSWEBROOT=OC_Config::getValue('appsurl', '');
		}elseif(file_exists(OC::$SERVERROOT.'/apps')){
			OC::$APPSROOT=OC::$SERVERROOT;
			OC::$APPSWEBROOT=OC::$WEBROOT;
		}elseif(file_exists(OC::$SERVERROOT.'/../apps')){
			OC::$APPSROOT=rtrim(dirname(OC::$SERVERROOT), '/');
			OC::$APPSWEBROOT=rtrim(dirname(OC::$WEBROOT), '/');
		}else{
			echo("apps directory not found! Please put the ownCloud apps folder in the ownCloud folder or the folder above. You can also configure the location in the config.php file.");
			exit;
		}

		// set the right include path
		set_include_path(
			OC::$SERVERROOT.'/lib'.PATH_SEPARATOR.
			OC::$SERVERROOT.'/config'.PATH_SEPARATOR.
			OC::$THIRDPARTYROOT.'/3rdparty'.PATH_SEPARATOR.
			OC::$APPSROOT.PATH_SEPARATOR.
			OC::$APPSROOT.'/apps'.PATH_SEPARATOR.
			get_include_path().PATH_SEPARATOR.
			OC::$SERVERROOT
		);
	}

	public static function checkInstalled() {
		// Redirect to installer if not installed
		if (!OC_Config::getValue('installed', false) && OC::$SUBURI != '/index.php') {
			$url = 'http://'.$_SERVER['SERVER_NAME'].OC::$WEBROOT.'/index.php';
			header("Location: $url");
			exit();
		}
	}

	public static function checkSSL() {
		// redirect to https site if configured
		if( OC_Config::getValue( "forcessl", false )){
			ini_set("session.cookie_secure", "on");
			if(OC_Helper::serverProtocol()<>'https') {
				$url = "https://". OC_Helper::serverHost() . $_SERVER['REQUEST_URI'];
				header("Location: $url");
				exit();
			}
		}
	}

	public static function checkUpgrade() {
		if(OC_Config::getValue('installed', false)){
			$installedVersion=OC_Config::getValue('version','0.0.0');
			$currentVersion=implode('.',OC_Util::getVersion());
			if (version_compare($currentVersion, $installedVersion, '>')) {
				OC_Log::write('core','starting upgrade from '.$installedVersion.' to '.$currentVersion,OC_Log::DEBUG);
				$result=OC_DB::updateDbFromStructure(OC::$SERVERROOT.'/db_structure.xml');
				if(!$result){
					echo 'Error while upgrading the database';
					die();
				}
				if(file_exists(OC::$SERVERROOT."/config/config.php") and !is_writable(OC::$SERVERROOT."/config/config.php")) {
					$tmpl = new OC_Template( '', 'error', 'guest' );
					$tmpl->assign('errors',array(1=>array('error'=>"Can't write into config directory 'config'",'hint'=>"You can usually fix this by giving the webserver user write access to the config directory in owncloud")));
					$tmpl->printPage();
					exit;
				}

				OC_Config::setValue('version',implode('.',OC_Util::getVersion()));
				OC_App::checkAppsRequirements();
			}

			OC_App::updateApps();
		}
	}

	public static function initTemplateEngine() {
		// if the formfactor is not yet autodetected do the autodetection now. For possible forfactors check the detectFormfactor documentation
		if(!isset($_SESSION['formfactor'])){
			$_SESSION['formfactor']=OC::detectFormfactor();
		}
		// allow manual override via GET parameter
		if(isset($_GET['formfactor'])){
			$_SESSION['formfactor']=$_GET['formfactor'];
		}

		// Add the stuff we need always
		OC_Util::addScript( "jquery-1.7.2.min" );
		OC_Util::addScript( "jquery-ui-1.8.16.custom.min" );
		OC_Util::addScript( "jquery-showpassword" );
		OC_Util::addScript( "jquery.infieldlabel.min" );
		OC_Util::addScript( "jquery-tipsy" );
		OC_Util::addScript( "oc-dialogs" );
		OC_Util::addScript( "js" );
		OC_Util::addScript( "eventsource" );
		OC_Util::addScript( "config" );
		//OC_Util::addScript( "multiselect" );
		OC_Util::addScript('search','result');
		OC_Util::addStyle( "styles" );
		OC_Util::addStyle( "multiselect" );
		OC_Util::addStyle( "jquery-ui-1.8.16.custom" );
		OC_Util::addStyle( "jquery-tipsy" );
	}

	public static function initSession() {
		ini_set('session.cookie_httponly','1;');
		session_start();
	}
	
	public static function loadapp(){
		if(file_exists(OC::$APPSROOT . '/apps/' . OC::$REQUESTEDAPP . '/index.php')){
			require_once(OC::$APPSROOT . '/apps/' . OC::$REQUESTEDAPP . '/index.php');
		}else{
			trigger_error('The requested App was not found.', E_USER_ERROR);//load default app instead?
		}
	}
	
	public static function loadfile(){
		if(file_exists(OC::$APPSROOT . '/apps/' . OC::$REQUESTEDAPP . '/' . OC::$REQUESTEDFILE)){
			if(substr(OC::$REQUESTEDFILE, -3) == 'css'){
				$appswebroot = (string) OC::$APPSWEBROOT;
				$webroot = (string) OC::$WEBROOT;
				$filepath = OC::$APPSROOT . '/apps/' . OC::$REQUESTEDAPP . '/' . OC::$REQUESTEDFILE;
				header('Content-Type: text/css');
				OC_Response::enableCaching();
				OC_Response::setLastModifiedHeader(filemtime($filepath));
				$cssfile = file_get_contents($filepath);
				$cssfile = str_replace('%appswebroot%', $appswebroot, $cssfile);
				$cssfile = str_replace('%webroot%', $webroot, $cssfile);
				OC_Response::setETagHeader(md5($cssfile));
				header('Content-Length: '.strlen($cssfile));
				echo $cssfile;
				exit;
			}elseif(substr(OC::$REQUESTEDFILE, -3) == 'php'){
				require_once(OC::$APPSROOT . '/apps/' . OC::$REQUESTEDAPP . '/' . OC::$REQUESTEDFILE);
			}	
		}else{
			header('HTTP/1.0 404 Not Found');
			exit;
		}
	}

	public static function init(){
		// register autoloader
		spl_autoload_register(array('OC','autoload'));
		setlocale(LC_ALL, 'en_US.UTF-8');
		
		// set some stuff
		//ob_start();
		error_reporting(E_ALL | E_STRICT);
		if (defined('DEBUG') && DEBUG){
			ini_set('display_errors', 1);
		}

		date_default_timezone_set('UTC');
		ini_set('arg_separator.output','&amp;');

                // try to switch magic quotes off.
                if(function_exists('set_magic_quotes_runtime')) {
                        @set_magic_quotes_runtime(false);
                }

		//try to configure php to enable big file uploads.
		//this doesn´t work always depending on the webserver and php configuration.
		//Let´s try to overwrite some defaults anyways
		
		//try to set the maximum execution time to 60min
		@set_time_limit(3600);
		@ini_set('max_execution_time',3600);
		@ini_set('max_input_time',3600);

		//try to set the maximum filesize to 10G
		@ini_set('upload_max_filesize','10G');
		@ini_set('post_max_size','10G');
		@ini_set('file_uploads','50');

		//try to set the session lifetime to 60min
		@ini_set('gc_maxlifetime','3600');


		//set http auth headers for apache+php-cgi work around
		if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches))
		{
			list($name, $password) = explode(':', base64_decode($matches[1]));
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}

		//set http auth headers for apache+php-cgi work around if variable gets renamed by apache
		if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) && preg_match('/Basic\s+(.*)$/i', $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], $matches))
		{
			list($name, $password) = explode(':', base64_decode($matches[1]));
			$_SERVER['PHP_AUTH_USER'] = strip_tags($name);
			$_SERVER['PHP_AUTH_PW'] = strip_tags($password);
		}
		
		self::initPaths();

		// register the stream wrappers
		require_once('streamwrappers.php');
		stream_wrapper_register("fakedir", "OC_FakeDirStream");
		stream_wrapper_register('static', 'OC_StaticStreamWrapper');
		stream_wrapper_register('close', 'OC_CloseStreamWrapper');

		self::checkInstalled();
		self::checkSSL();

                // CSRF protection
                if(isset($_SERVER['HTTP_REFERER'])) $referer=$_SERVER['HTTP_REFERER']; else $referer='';
                $refererhost=parse_url($referer);
                if(isset($refererhost['host'])) $refererhost=$refererhost['host']; else $refererhost='';
                $server=OC_Helper::serverHost();
                $serverhost=explode(':',$server);
                $serverhost=$serverhost['0']; 
		if(($_SERVER['REQUEST_METHOD']=='POST') and ($refererhost<>$serverhost)) {
			$url = OC_Helper::serverProtocol().'://'.$server.OC::$WEBROOT.'/index.php';
			header("Location: $url");
			exit();
		}

		self::initSession();
		self::initTemplateEngine();
		self::checkUpgrade();

		$errors=OC_Util::checkServer();
		if(count($errors)>0) {
			OC_Template::printGuestPage('', 'error', array('errors' => $errors));
			exit;
		}

		// TODO: we should get rid of this one, too
		// WARNING: to make everything even more confusing,
		//   DATADIRECTORY is a var that changes and DATADIRECTORY_ROOT
		//   stays the same, but is set by "datadirectory".
		//   Any questions?
		OC::$CONFIG_DATADIRECTORY = OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" );

		// User and Groups
		if( !OC_Config::getValue( "installed", false )){
			$_SESSION['user_id'] = '';
		}


		OC_User::useBackend( OC_Config::getValue( "userbackend", "database" ));
		OC_Group::useBackend(new OC_Group_Database());

		// Set up file system unless forbidden
		global $RUNTIME_NOSETUPFS;
		if(!$RUNTIME_NOSETUPFS ){
			OC_Util::setupFS();
		}

		// Load Apps
		// This includes plugins for users and filesystems as well
		global $RUNTIME_NOAPPS;
		global $RUNTIME_APPTYPES;
		if(!$RUNTIME_NOAPPS ){
			if($RUNTIME_APPTYPES){
				OC_App::loadApps($RUNTIME_APPTYPES);
			}else{
				OC_App::loadApps();
			}
		}
		
		// Check for blacklisted files
		OC_Hook::connect('OC_Filesystem','write','OC_Filesystem','isBlacklisted');
		OC_Hook::connect('OC_Filesystem', 'rename', 'OC_Filesystem', 'isBlacklisted');

		//make sure temporary files are cleaned up
		register_shutdown_function(array('OC_Helper','cleanTmp'));
		
		//parse the given parameters
		self::$REQUESTEDAPP = (isset($_GET['app']) && trim($_GET['app']) != '' && !is_null($_GET['app'])?str_replace(array('\0', '/', '\\', '..'), '', strip_tags($_GET['app'])):OC_Config::getValue('defaultapp', 'files'));
		if(substr_count(self::$REQUESTEDAPP, '?') != 0){
			$app = substr(self::$REQUESTEDAPP, 0, strpos(self::$REQUESTEDAPP, '?'));
			$param = substr(self::$REQUESTEDAPP, strpos(self::$REQUESTEDAPP, '?') + 1);
			parse_str($param, $get);
			$_GET = array_merge($_GET, $get);
			self::$REQUESTEDAPP = $app;
			$_GET['app'] = $app;
		}
		self::$REQUESTEDFILE = (isset($_GET['getfile'])?$_GET['getfile']:null);
		if(substr_count(self::$REQUESTEDFILE, '?') != 0){
			$file = substr(self::$REQUESTEDFILE, 0, strpos(self::$REQUESTEDFILE, '?'));
			$param = substr(self::$REQUESTEDFILE, strpos(self::$REQUESTEDFILE, '?') + 1);
			parse_str($param, $get);
			$_GET = array_merge($_GET, $get);
			self::$REQUESTEDFILE = $file;
			$_GET['getfile'] = $file;
		}
		if(!is_null(self::$REQUESTEDFILE)){
			$subdir = OC::$APPSROOT . '/apps/' . self::$REQUESTEDAPP . '/' . self::$REQUESTEDFILE;
			$parent = OC::$APPSROOT . '/apps/' . self::$REQUESTEDAPP;
			if(!OC_Helper::issubdirectory($subdir, $parent)){
				self::$REQUESTEDFILE = null;
				header('HTTP/1.0 404 Not Found');
				exit;
			}
		}
	}
}

// define runtime variables - unless this already has been done
if( !isset( $RUNTIME_NOSETUPFS )){
	$RUNTIME_NOSETUPFS = false;
}
if( !isset( $RUNTIME_NOAPPS )){
	$RUNTIME_NOAPPS = false;
}

if(!function_exists('get_temp_dir')) {
	function get_temp_dir() {
		if( $temp=ini_get('upload_tmp_dir') )        return $temp;
		if( $temp=getenv('TMP') )        return $temp;
		if( $temp=getenv('TEMP') )        return $temp;
		if( $temp=getenv('TMPDIR') )    return $temp;
		$temp=tempnam(__FILE__,'');
		if (file_exists($temp)) {
			unlink($temp);
			return dirname($temp);
		}
		if( $temp=sys_get_temp_dir())    return $temp;
		
		return null;
	}
}

OC::init();
