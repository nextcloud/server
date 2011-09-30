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
OC_Util::checkAppEnabled('unhosted');
require_once('Sabre/autoload.php');
require_once('lib_unhosted.php');
require_once('oauth_ro_auth.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

//allow use as unhosted storage for other websites
if(isset($_SERVER['HTTP_ORIGIN'])) {
	header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
	header('Access-Control-Max-Age: 3600');
	header('Access-Control-Allow-Methods: OPTIONS, GET, PUT, DELETE, PROPFIND');
  	header('Access-Control-Allow-Headers: Authorization');
} else {
	header('Access-Control-Allow-Origin: *');
}

$path = substr($_SERVER["REQUEST_URI"], strlen($_SERVER["SCRIPT_NAME"]));
$pathParts =  explode('/', $path);
// for webdav:
// 0/     1       /   2    /   3  /   4     /    5     /   6     / 7
//  /$ownCloudUser/unhosted/webdav/$userHost/$userName/$dataScope/$key
// for oauth:
// 0/      1      /  2     /  3  / 4
//  /$ownCloudUser/unhosted/oauth/auth

if(count($pathParts) >= 8 && $pathParts[0] == '' && $pathParts[2] == 'unhosted' && $pathParts[3] == 'webdav') {
	list($dummy0, $ownCloudUser, $dummy2, $dummy3, $userHost, $userName, $dataScope) = $pathParts;

	OC_Util::setupFS($ownCloudUser);

	// Create ownCloud Dir
	$publicDir = new OC_Connector_Sabre_Directory('');
	$server = new Sabre_DAV_Server($publicDir);

	// Path to our script
	$server->setBaseUri(OC::$WEBROOT."/apps/unhosted/compat.php/$ownCloudUser");

	// Auth backend
	$authBackend = new OC_Connector_Sabre_Auth_ro_oauth(OC_UnhostedWeb::getValidTokens($ownCloudUser, $userName.'@'.$userHost, $dataScope));

	$authPlugin = new Sabre_DAV_Auth_Plugin($authBackend,'ownCloud');//should use $validTokens here
	$server->addPlugin($authPlugin);

	// Also make sure there is a 'data' directory, writable by the server. This directory is used to store information about locks
	$lockBackend = new OC_Connector_Sabre_Locks();
	$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
	$server->addPlugin($lockPlugin);

	// And off we go!
	$server->exec();
} else if(count($pathParts) >= 4 && $pathParts[0] == '' && $pathParts[2] == 'unhosted' && $pathParts[3] == 'oauth2' && $pathParts[4] = 'auth') {
	if(isset($_POST['allow'])) {
		//TODO: input checking. these explodes may fail to produces the desired arrays:
		$ownCloudUser = $pathParts[1];
		foreach($_GET as $k => $v) {
			if($k=='user_address'){
				$userAddress=$v;
			} else if($k=='redirect_uri'){
				$appUrl=$v;
			} else if($k=='scope'){
				$dataScope=$v;
			}
		}
		if(OC_User::getUser() == $ownCloudUser) {
			//TODO: check if this can be faked by editing the cookie in firebug!
			$token=OC_UnhostedWeb::createDataScope($appUrl, $userAddress, $dataScope);
			header('Location: '.$_GET['redirect_uri'].'#access_token='.$token.'&token_type=unhosted');
		} else {
			if($_SERVER['HTTPS']){
				$url = "https://";
			} else {
				$url = "http://";
			}
			$url .= $_SERVER['SERVER_NAME'];
			$url .= substr($_SERVER['SCRIPT_NAME'], 0, -strlen('apps/unhosted/compat.php'));
			die('Please '
				.'<input type="submit" onclick="'
				."window.open('$url','Close me!','height=600,width=300');"
				.'" value="log in">'
				.', close the pop-up, and '
				.'<form method="POST"><input name="allow" type="submit" value="Try again"></form>');
		}
	} else {
		echo '<form method="POST"><input name="allow" type="submit" value="Allow this web app to store stuff on your owncloud."></form>';
	}
} else {
	die('not webdav and not oauth. dont know what to do '.var_export($pathParts, true));
}
