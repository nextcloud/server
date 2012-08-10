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
if($not_installed) {
	// Check for autosetup:
	$autosetup_file = OC::$SERVERROOT."/config/autoconfig.php";
	if( file_exists( $autosetup_file )){
		OC_Log::write('core','Autoconfig file found, setting up owncloud...',OC_Log::INFO);
		include( $autosetup_file );
		$_POST['install'] = 'true';
		$_POST = array_merge ($_POST, $AUTOCONFIG);
	        unlink($autosetup_file);
	}
	OC_Util::addScript('setup');
	require_once('setup.php');
	exit();
}

// Handle WebDAV
if($_SERVER['REQUEST_METHOD']=='PROPFIND'){
	header('location: '.OC_Helper::linkToRemote('webdav'));
	exit();
}
elseif(!OC_User::isLoggedIn() && substr(OC::$REQUESTEDFILE,-3) == 'css'){
	OC_App::loadApps();
	OC::loadfile();
}
// Someone is logged in :
elseif(OC_User::isLoggedIn()) {
	OC_App::loadApps();
	if(isset($_GET["logout"]) and ($_GET["logout"])) {
		OC_User::logout();
		header("Location: ".OC::$WEBROOT.'/');
		exit();
	}else{
		if(is_null(OC::$REQUESTEDFILE)){
			OC::loadapp();
		}else{
			OC::loadfile();
		}
	}

// For all others cases, we display the guest page :
} else {
	OC_App::loadApps();
	$error = false;
	// remember was checked after last login
	if(isset($_COOKIE["oc_remember_login"]) && isset($_COOKIE["oc_token"]) && isset($_COOKIE["oc_username"]) && $_COOKIE["oc_remember_login"]) {
		if(defined("DEBUG") && DEBUG) {
			OC_Log::write('core','Trying to login from cookie',OC_Log::DEBUG);
		}
		// confirm credentials in cookie
		if(isset($_COOKIE['oc_token']) && OC_User::userExists($_COOKIE['oc_username']) &&
		OC_Preferences::getValue($_COOKIE['oc_username'], "login", "token") === $_COOKIE['oc_token']) {
			OC_User::setUserId($_COOKIE['oc_username']);
			OC_Util::redirectToDefaultPage();
		}
		else {
			OC_User::unsetMagicInCookie();
		}

	// Someone wants to log in :
	} elseif(isset($_POST["user"]) and isset($_POST['password']) and isset($_SESSION['sectoken']) and isset($_POST['sectoken']) and ($_SESSION['sectoken']==$_POST['sectoken']) ) {
		if(OC_User::login($_POST["user"], $_POST["password"])) {
			if(!empty($_POST["remember_login"])){
				if(defined("DEBUG") && DEBUG) {
					OC_Log::write('core','Setting remember login to cookie',OC_Log::DEBUG);
				}
				$token = md5($_POST["user"].time().$_POST['password']);
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
	
	// The user is already authenticated using Apaches AuthType Basic... very usable in combination with LDAP
	} elseif(isset($_SERVER["PHP_AUTH_USER"]) && isset($_SERVER["PHP_AUTH_PW"])){
		if (OC_User::login($_SERVER["PHP_AUTH_USER"],$_SERVER["PHP_AUTH_PW"]))	{
			//OC_Log::write('core',"Logged in with HTTP Authentication",OC_Log::DEBUG);
			OC_User::unsetMagicInCookie();
			$_REQUEST['redirect_url'] = (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:'');
			OC_Util::redirectToDefaultPage();
		}else{
			$error = true;
		}
	}
	if(!array_key_exists('sectoken', $_SESSION) || (array_key_exists('sectoken', $_SESSION) && is_null(OC::$REQUESTEDFILE)) || substr(OC::$REQUESTEDFILE, -3) == 'php'){
		$sectoken=rand(1000000,9999999);
		$_SESSION['sectoken']=$sectoken;
		$redirect_url = (isset($_REQUEST['redirect_url'])) ? OC_Util::sanitizeHTML($_REQUEST['redirect_url']) : $_SERVER['REQUEST_URI'];
		OC_Template::printGuestPage('', 'login', array('error' => $error, 'sectoken' => $sectoken, 'redirect' => $redirect_url));
	}
}
