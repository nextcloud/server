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
	 * SPL autoload
	 */
	public static function autoload($className){
		if(array_key_exists($className,OC::$CLASSPATH)){
			require_once OC::$CLASSPATH[$className];
		}
		elseif(strpos($className,'OC_')===0){
			require_once strtolower(str_replace('_','/',substr($className,3)) . '.php');
		}
		elseif(strpos($className,'Sabre_')===0) {
			require_once str_replace('_','/',$className) . '.php';
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

	public static function init(){
		// register autoloader
		spl_autoload_register(array('OC','autoload'));

		// set some stuff
		//ob_start();
		error_reporting(E_ALL | E_STRICT);
		if (defined('DEBUG') && DEBUG){
			ini_set('display_errors', 1);
		}

		date_default_timezone_set('Europe/Berlin');
		ini_set('arg_separator.output','&amp;');


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

		// calculate the documentroot
		OC::$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
		OC::$SERVERROOT=str_replace("\\",'/',substr(__FILE__,0,-13));
		OC::$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen(OC::$SERVERROOT));
		$scriptName=$_SERVER["SCRIPT_NAME"];
		if(substr($scriptName,-1)=='/'){
			$scriptName.='index.php';
		}
		OC::$WEBROOT=substr($scriptName,0,strlen($scriptName)-strlen(OC::$SUBURI));

		if(OC::$WEBROOT!='' and OC::$WEBROOT[0]!=='/'){
			OC::$WEBROOT='/'.OC::$WEBROOT;
		}

		// set the right include path
		set_include_path(OC::$SERVERROOT.'/lib'.PATH_SEPARATOR.OC::$SERVERROOT.'/config'.PATH_SEPARATOR.OC::$SERVERROOT.'/3rdparty'.PATH_SEPARATOR.get_include_path().PATH_SEPARATOR.OC::$SERVERROOT);

		// Redirect to installer if not installed
		if (!OC_Config::getValue('installed', false) && OC::$SUBURI != '/index.php') {
			$url = 'http://'.$_SERVER['SERVER_NAME'].OC::$WEBROOT.'/index.php';
			header("Location: $url");
			exit();
		}

		// redirect to https site if configured
		if( OC_Config::getValue( "forcessl", false )){
			ini_set("session.cookie_secure", "on");
			if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
				$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				header("Location: $url");
				exit();
			}
		}

		if(OC_Config::getValue('installed', false)){
			$installedVersion=OC_Config::getValue('version','0.0.0');
			$currentVersion=implode('.',OC_Util::getVersion());
			if (version_compare($currentVersion, $installedVersion, '>')) {
				$result=OC_DB::updateDbFromStructure(OC::$SERVERROOT.'/db_structure.xml');
				if(!$result){
					echo 'Error while upgrading the database';
					die();
				}
				OC_Config::setValue('version',implode('.',OC_Util::getVersion()));
			}

			OC_App::updateApps();
		}

		ini_set('session.cookie_httponly','1;');
		session_start();

		// if the formfactor is not yet autodetected do the autodetection now. For possible forfactors check the detectFormfactor documentation
		if(!isset($_SESSION['formfactor'])){
			$_SESSION['formfactor']=OC::detectFormfactor();
		}
		// allow manual override via GET parameter
		if(isset($_GET['formfactor'])){
			$_SESSION['formfactor']=$_GET['formfactor'];
		}


		// Add the stuff we need always
		OC_Util::addScript( "jquery-1.6.4.min" );
		OC_Util::addScript( "jquery-ui-1.8.16.custom.min" );
		OC_Util::addScript( "jquery-showpassword" );
		OC_Util::addScript( "jquery.infieldlabel.min" );
		OC_Util::addScript( "jquery-tipsy" );
		OC_Util::addScript( "js" );
		//OC_Util::addScript( "multiselect" );
		OC_Util::addScript('search','result');
		OC_Util::addStyle( "styles" );
		OC_Util::addStyle( "multiselect" );
		OC_Util::addStyle( "jquery-ui-1.8.16.custom" );
		OC_Util::addStyle( "jquery-tipsy" );

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
		OC_Group::setBackend( OC_Config::getValue( "groupbackend", "database" ));

		// Was in required file ... put it here
		OC_Filesystem::registerStorageType('local','OC_Filestorage_Local',array('datadir'=>'string'));

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

		OC_Files::cleanTmpFiles();

		// Last part: connect some hooks
		OC_HOOK::connect('OC_User', 'post_createUser', 'OC_Connector_Sabre_Principal', 'addPrincipal');
		OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_Connector_Sabre_Principal', 'deletePrincipal');
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
		return null;
	}
}

OC::init();

require_once('fakedirstream.php');



// FROM search.php
new OC_Search_Provider_File();
