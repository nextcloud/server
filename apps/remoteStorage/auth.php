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
OC_Util::checkAppEnabled('remoteStorage');
require_once('Sabre/autoload.php');
require_once('lib_remoteStorage.php');
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
// 0/     1       /   2    /   3  /   4     /    5     /   6     / 7
//  /$ownCloudUser/remoteStorage/webdav/$userHost/$userName/$dataScope/$key
// for oauth:
// 0/      1      /  2     /  3  / 4
//  /$ownCloudUser/remoteStorage/oauth/auth

if(count($pathParts) == 2 && $pathParts[0] == '') {
	//TODO: input checking. these explodes may fail to produces the desired arrays:
	$subPathParts = explode('?', $pathParts[1]);
	$ownCloudUser = $subPathParts[0];
	foreach($_GET as $k => $v) {
		if($k=='user_address'){
			$userAddress=$v;
		} else if($k=='redirect_uri'){
			$appUrl=$v;
		} else if($k=='scope'){
			$category=$v;
		}
	}
	$currUser = OC_User::getUser();
	if($currUser == $ownCloudUser) {
		if(isset($_POST['allow'])) {
			//TODO: check if this can be faked by editing the cookie in firebug!
			$token=OC_remoteStorage::createCategory($appUrl, $category);
			header('Location: '.$_GET['redirect_uri'].'#access_token='.$token.'&token_type=bearer');
		} else {
			echo '<form method="POST"><input name="allow" type="submit" value="Allow this web app to store stuff on your owncloud."></form>';
		}
	} else {
		if((isset($_SERVER['HTTPS'])) && ($_SERVER['HTTPS'])) {
			$url = "https://";
		} else {
			$url = "http://";
		}
		$url .= $_SERVER['SERVER_NAME'];
		$url .= substr($_SERVER['SCRIPT_NAME'], 0, -strlen('apps/remoteStorage/compat.php'));
		die('You are '.($currUser?'logged in as '.$currUser.' instead of '.$ownCloudUser:'not logged in').'. Please '
			.'<input type="submit" onclick="'
			."window.open('$url','Close me!','height=600,width=300');"
			.'" value="log in">'
			.', close the pop-up, and '
			.'<form method="POST"><input name="allow" type="submit" value="Click here"></form>');
	}
} else {
	die('please use auth.php/username?params. '.var_export($pathParts, true));
}
