<?php

/**
* ownCloud
*
* Original:
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
* 
* Adapted:
* @author Michiel de Jong, 2012
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.	If not, see <http://www.gnu.org/licenses/>.
*
*/

header("X-Frame-Options: Sameorigin");

// Do not load FS ...
$RUNTIME_NOSETUPFS = true;

OCP\App::checkAppEnabled('remoteStorage');
require_once('Sabre/autoload.php');
require_once('lib_remoteStorage.php');
require_once('oauth_ro_auth.php');

ini_set('default_charset', 'UTF-8');
#ini_set('error_reporting', '');
@ob_clean();

foreach($_GET as $k => $v) {
  if($k=='userid'){
    $userId=$v;
  } else if($k=='redirect_uri'){
    $appUrlParts=explode('/', $v);
    $appUrl = htmlentities($appUrlParts[2]);//TODO: check if this is equal to client_id
  } else if($k=='scope'){
    $categories=htmlentities($v);
  }
}
$currUser = OCP\USER::getUser();
if($userId && $appUrl && $categories) {
  if($currUser == $userId) {
    if(isset($_POST['allow'])) {
      //TODO: check if this can be faked by editing the cookie in firebug!
      $token=OC_remoteStorage::createCategories($appUrl, $categories);
      header('Location: '.$_GET['redirect_uri'].'#access_token='.$token.'&token_type=bearer');
    } else if($existingToken = OC_remoteStorage::getTokenFor($appUrl, $categories)) {
      header('Location: '.$_GET['redirect_uri'].'#access_token='.$existingToken.'&token_type=bearer');
    } else {
      //params ok, logged in ok, but need to click Allow still:
	$appUrlParts = explode('/', $_GET['redirect_uri']);
	$host = $appUrlParts[2];
	$categories = explode(',', $_GET['scope']);
	OCP\Util::addStyle('', 'auth');
	OCP\Template::printGuestPage('remoteStorage', 'auth', array(
		'host' => $host,
		'categories' => $categories,
	));
	}//end 'need to click Allow still'
	} else {//login not ok
		if($currUser) {
			die('You are logged in as '.$currUser.' instead of '.htmlentities($userId));
		} else {
			// this will display the login page for us
			OCP\Util::checkLoggedIn();
		}
	}
} else {//params not ok
	die('please use e.g. '.OCP\Util::linkTo('remoteStorage', 'auth.php').'?userid=admin&redirect_uri=http://host/path&scope=...');
}
