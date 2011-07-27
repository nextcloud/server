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

// Get rid of this stupid require_once OC_...
function OC_autoload($className) {
	if(strpos($className,'OC_')===0) {
		require_once strtolower(str_replace('_','/',substr($className,3)) . '.php');
	}
}

spl_autoload_register('OC_autoload');

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
if(substr($scriptName,-1)=='/'){
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

$error=(count(OC_UTIL::checkServer())>0);

// User and Groups
if( !OC_CONFIG::getValue( "installed", false )){
	$_SESSION['user_id'] = '';
}

OC_USER::useBackend( OC_CONFIG::getValue( "userbackend", "database" ));
OC_GROUP::setBackend( OC_CONFIG::getValue( "groupbackend", "database" ));

// Was in required file ... put it here
OC_FILESYSTEM::registerStorageType('local','OC_FILESTORAGE_LOCAL',array('datadir'=>'string'));

// Set up file system unless forbidden
if(!$error and !$RUNTIME_NOSETUPFS ){
	OC_UTIL::setupFS();
}

// Add the stuff we need always
OC_UTIL::addScript( "jquery-1.6.2.min" );
OC_UTIL::addScript( "jquery-ui-1.8.14.custom.min" );
OC_UTIL::addScript( "js" );
OC_UTIL::addStyle( "jquery-ui-1.8.14.custom" );
OC_UTIL::addStyle( "styles" );

// Load Apps
if(!$error and !$RUNTIME_NOAPPS ){
	OC_APP::loadApps();
}


?>
