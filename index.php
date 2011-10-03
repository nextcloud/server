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

$RUNTIME_NOAPPS = TRUE; //no apps, yet

require_once('lib/base.php');

// Setup required :
$not_installed = !OC_Config::getValue('installed', false);
$install_called = (isset($_POST['install']) AND $_POST['install']=='true');
if($not_installed) {
	OC_Util::addScript('setup');
	require_once('setup.php');
	exit();
}

// Handle WebDAV
if($_SERVER['REQUEST_METHOD']=='PROPFIND'){
	header('location: '.OC_Helper::linkTo('files','webdav.php'));
	exit();
}

// Someone is logged in :
elseif(OC_User::isLoggedIn()) {
	if(isset($_GET["logout"]) and ($_GET["logout"])) {
		OC_User::logout();
		header("Location: ".OC::$WEBROOT.'/');
		exit();
	}
	else {
		OC_Util::redirectToDefaultPage();
	}
}

// For all others cases, we display the guest page :
else {
	OC_App::loadApps();
	$error = false;

	// remember was checked after last login
	if(isset($_COOKIE["oc_remember_login"]) && isset($_COOKIE["oc_token"]) && isset($_COOKIE["oc_username"]) && $_COOKIE["oc_remember_login"]) {
		if(defined("DEBUG") && DEBUG) {
			error_log("Trying to login from cookie");
		}
		// confirm credentials in cookie
		if(isset($_COOKIE['oc_token']) && OC_User::userExists($_COOKIE['oc_username']) &&
		OC_Preferences::getValue($_COOKIE['oc_username'], "login", "token") == $_COOKIE['oc_token']) {
			OC_User::setUserId($_COOKIE['oc_username']);
			OC_Util::redirectToDefaultPage();
		}
	}
	
	// Someone wants to log in :
	elseif(isset($_POST["user"]) && isset($_POST['password'])) {
		if(OC_User::login($_POST["user"], $_POST["password"])) {
			if(!empty($_POST["remember_login"])){
				if(defined("DEBUG") && DEBUG) {
					error_log("Setting remember login to cookie");
				}
				$token = md5($_POST["user"].time());
				OC_Preferences::setValue($_POST['user'], 'login', 'token', $token);
				OC_User::setMagicInCookie($_POST["user"], $token);
			}
			else {
				OC_User::unsetMagicInCookie();
			}
			OC_Util::redirectToDefaultPage();
		} else {
			$error = true;
		}
	}

	OC_Template::printGuestPage('', 'login', array('error' => $error ));
}
