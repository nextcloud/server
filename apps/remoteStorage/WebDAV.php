<?php

/**
* ownCloud
*
* Original:
* @author Frank Karlitschek
* @copyright 2010 Frank Karlitschek karlitschek@kde.org
* 
* Adapted:
* @author Michiel de Jong, 2011
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


// Do not load FS ...
$RUNTIME_NOSETUPFS = true;


require_once('../../lib/base.php');

require_once('../../lib/user.php');
require_once('../../lib/public/user.php');

require_once('../../lib/app.php');
require_once('../../lib/public/app.php');

require_once('../../3rdparty/Sabre/DAV/Auth/IBackend.php');
require_once('../../3rdparty/Sabre/DAV/Auth/Backend/AbstractBasic.php');
require_once('../../lib/connector/sabre/auth.php');

OCP\App::checkAppEnabled('remoteStorage');
require_once('lib_remoteStorage.php');
require_once('BearerAuth.php');
require_once('oauth_ro_auth.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

//allow use as remote storage for other websites
if(isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Max-Age: 3600');
	header('Access-Control-Allow-Methods: OPTIONS, GET, PUT, DELETE, PROPFIND');
  	header('Access-Control-Allow-Headers: Authorization, Content-Type');
} else {
	header('Access-Control-Allow-Origin: *');
}

$path = substr($_SERVER["REQUEST_URI"], strlen($_SERVER["SCRIPT_NAME"]));
$pathParts =  explode('/', $path);
// for webdav:
// 0/     1       /   2    /   3...
//  /$ownCloudUser/remoteStorage/$category/

if(count($pathParts) >= 3 && $pathParts[0] == '') {
	list($dummy, $ownCloudUser, $dummy2, $category) = $pathParts;

	OC_Util::setupFS($ownCloudUser);

	// Create ownCloud Dir
	$publicDir = new OC_Connector_Sabre_Directory('');
	$server = new Sabre_DAV_Server($publicDir);

	// Path to our script
	$server->setBaseUri(OC::$WEBROOT."/apps/remoteStorage/WebDAV.php/$ownCloudUser");

	// Auth backend
	$authBackend = new OC_Connector_Sabre_Auth_ro_oauth(
      OC_remoteStorage::getValidTokens($ownCloudUser, $category),
      $category
      );

	$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud');//should use $validTokens here
	$server->addPlugin($authPlugin);

	// Also make sure there is a 'data' directory, writable by the server. This directory is used to store information about locks
	$lockBackend = new OC_Connector_Sabre_Locks();
	$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
	$server->addPlugin($lockPlugin);

	// And off we go!
	$server->exec();
} else {
	//die('not the right address format '.var_export($pathParts, true));
	die('not the right address format');
}
