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
	}
}

// this requires all our OC_* classes
spl_autoload_register(array('OC','autoload'));

// set some stuff
//ob_start();
error_reporting(E_ALL | E_STRICT);

date_default_timezone_set('Europe/Berlin');
ini_set('arg_separator.output','&amp;');
ini_set('session.cookie_httponly','1;');
session_start();

// calculate the documentroot
$DOCUMENTROOT=realpath($_SERVER['DOCUMENT_ROOT']);
$SERVERROOT=str_replace("\\",'/',substr(__FILE__,0,-13));
$SUBURI=substr(realpath($_SERVER["SCRIPT_FILENAME"]),strlen($SERVERROOT));
$scriptName=$_SERVER["SCRIPT_NAME"];
if(substr($scriptName,-1)=='/'){
	$scriptName.='index.php';
}
$WEBROOT=substr($scriptName,0,strlen($scriptName)-strlen($SUBURI));



if($WEBROOT!='' and $WEBROOT[0]!=='/'){
	$WEBROOT='/'.$WEBROOT;
}

// set the right include path
set_include_path($SERVERROOT.'/lib'.PATH_SEPARATOR.$SERVERROOT.'/config'.PATH_SEPARATOR.$SERVERROOT.'/3dparty'.PATH_SEPARATOR.get_include_path().PATH_SEPARATOR.$SERVERROOT);

//Some libs we really depend on
require_once('Sabre/autoload.php');

// define runtime variables - unless this already has been done
if( !isset( $RUNTIME_NOSETUPFS )){
	$RUNTIME_NOSETUPFS = false;
}
if( !isset( $RUNTIME_NOAPPS )){
	$RUNTIME_NOAPPS = false;
}

// TODO: we should get rid of this one, too
// WARNING: to make everything even more confusing, DATADIRECTORY is a var that
//   changes and DATATIRECTORY_ROOT stays the same, but is set by
//   "datadirectory". Any questions?
$CONFIG_DATADIRECTORY = OC_Config::getValue( "datadirectory", "$SERVERROOT/data" );

// redirect to https site if configured
if( OC_Config::getValue( "forcessl", false )){
	if(!isset($_SERVER['HTTPS']) or $_SERVER['HTTPS'] != 'on') {
		$url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		header("Location: $url");
		exit();
	}
}

$error=(count(OC_Util::checkServer())>0);

// User and Groups
if( !OC_Config::getValue( "installed", false )){
	$_SESSION['user_id'] = '';
}


OC_User::useBackend( OC_Config::getValue( "userbackend", "database" ));
OC_Group::setBackend( OC_Config::getValue( "groupbackend", "database" ));

// Add the stuff we need always
OC_Util::addScript( "jquery-1.6.2.min" );
OC_Util::addScript( "jquery-ui-1.8.14.custom.min" );
OC_Util::addScript( "js" );
OC_Util::addScript('search','result');
OC_Util::addStyle( "jquery-ui-1.8.14.custom" );
OC_Util::addStyle( "styles" );

// Load Apps
// This includes plugins for users and filesystems as well
if(!$error and !$RUNTIME_NOAPPS ){
	OC_App::loadApps();
}

// Was in required file ... put it here
OC_Filesystem::registerStorageType('local','OC_Filestorage_Local',array('datadir'=>'string'));

// Set up file system unless forbidden
if(!$error and !$RUNTIME_NOSETUPFS ){
	OC_Util::setupFS();
}

// Last part: connect some hooks
OC_HOOK::connect('OC_User', 'post_createUser', 'OC_Connector_Sabre_Principal', 'addPrincipal');
OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_Connector_Sabre_Principal', 'deletePrincipal');


// FROM Connect.php
function OC_CONNECT_TEST($path,$user,$password){
	echo 'connecting...';
	$remote=OC_Connect::connect($path,$user,$password);
	if($remote->connected){
		echo 'done<br/>';
		if($remote->isLoggedIn()){
			echo 'logged in, session working<br/>';
			echo 'trying to get remote files...';
			$files=$remote->getFiles('');
			if($files){
				echo count($files).' files found:<br/>';
				foreach($files as $file){
					echo "{$file['type']} {$file['name']}: {$file['size']} bytes<br/>";
				}
				echo 'getting file "'.$file['name'].'"...';
				$size=$file['size'];
				$file=$remote->getFile('',$file['name']);
				if(file_exists($file)){
					$newSize=filesize($file);
					if($size!=$newSize){
						echo "fail<br/>Error: $newSize bytes received, $size expected.";
						echo '<br/><br/>Recieved file:<br/>';
						readfile($file);
						unlink($file);
						return;
					}
					OC_Filesystem::fromTmpFile($file,'/remoteFile');
					echo 'done<br/>';
					echo 'sending file "burning_avatar.png"...';
					$res=$remote->sendFile('','burning_avatar.png','','burning_avatar.png');
					if($res){
						echo 'done<br/>';
					}else{
						echo 'fail<br/>';
					}
				}else{
					echo 'fail<br/>';
				}
			}else{
				echo 'fail<br/>';
			}
		}else{
			echo 'no longer logged in, session fail<br/>';
		}
	}else{
		echo 'fail<br/>';
	}
	$remote->disconnect();
	die();
}

// From files.php
function zipAddDir($dir,$zip,$internalDir=''){
    $dirname=basename($dir);
    $zip->addEmptyDir($internalDir.$dirname);
    $internalDir.=$dirname.='/';
    $files=OC_Files::getdirectorycontent($dir);
    foreach($files as $file){
        $filename=$file['name'];
        $file=$dir.'/'.$filename;
        if(OC_Filesystem::is_file($file)){
			$tmpFile=OC_Filesystem::toTmpFile($file);
			OC_Files::$tmpFiles[]=$tmpFile;
            $zip->addFile($tmpFile,$internalDir.$filename);
        }elseif(OC_Filesystem::is_dir($file)){
            zipAddDir($file,$zip,$internalDir);
        }
    }
}

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
