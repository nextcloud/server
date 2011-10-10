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

	public static function init(){
		// register autoloader
		spl_autoload_register(array('OC','autoload'));

		// set some stuff
		//ob_start();
		error_reporting(E_ALL | E_STRICT);

		date_default_timezone_set('Europe/Berlin');
		ini_set('arg_separator.output','&amp;');

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

		// redirect to https site if configured
		if( OC_Config::getValue( "forcessl", false )){
			ini_set("session.cookie_secure", "on");
			if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
				$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				header("Location: $url");
				exit();
			}
		}

		ini_set('session.cookie_httponly','1;');
		session_start();

		// Add the stuff we need always
		OC_Util::addScript( "jquery-1.6.4.min" );
		OC_Util::addScript( "jquery-ui-1.8.14.custom.min" );
		OC_Util::addScript( "jquery-showpassword" );
		OC_Util::addScript( "jquery.infieldlabel.min" );
		OC_Util::addScript( "jquery-tipsy" );
		OC_Util::addScript( "js" );
		//OC_Util::addScript( "multiselect" );
		OC_Util::addScript('search','result');
		OC_Util::addStyle( "styles" );
		OC_Util::addStyle( "multiselect" );
		OC_Util::addStyle( "jquery-ui-1.8.14.custom" );
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

		// Load Apps
		// This includes plugins for users and filesystems as well
		global $RUNTIME_NOAPPS;
		if(!$RUNTIME_NOAPPS ){
			OC_App::loadApps();
		}

		// Was in required file ... put it here
		OC_Filesystem::registerStorageType('local','OC_Filestorage_Local',array('datadir'=>'string'));

		// Set up file system unless forbidden
		global $RUNTIME_NOSETUPFS;
		if(!$RUNTIME_NOSETUPFS ){
			OC_Util::setupFS();
		}

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

OC::init();

if(!function_exists('sys_get_temp_dir')) {
    function sys_get_temp_dir() {
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

require_once('fakedirstream.php');

// FROM search.php
new OC_Search_Provider_File();
