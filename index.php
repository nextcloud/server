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

OC_Util::addScript('setup');

$not_installed = !OC_Config::getValue('installed', false);
$install_called = (isset($_POST['install']) AND $_POST['install']=='true');
// First step : check if the server is correctly configured for ownCloud :
$errors = OC_Util::checkServer();
if(count($errors) > 0) {
	OC_Template::printGuestPage("", "error", array("errors" => $errors));
}

// Setup required :
elseif($not_installed OR $install_called) {
	require_once('setup.php');
}

if($_SERVER['REQUEST_METHOD']=='PROPFIND'){//handle webdav
	header('location: '.OC_Helper::linkTo('files','webdav.php'));
	exit();
}

// Someone is logged in :
elseif(OC_User::isLoggedIn()) {
	if(isset($_GET["logout"]) and ($_GET["logout"])) {
		OC_User::logout();
		header("Location: ".$WEBROOT.'/');
		exit();
	}
	else {
		header("Location: ".$WEBROOT.'/'.OC_Appconfig::getValue("core", "defaultpage", "files/index.php"));
		exit();
	}
}

// Someone wants to log in :
elseif(isset($_POST["user"])) {
	OC_App::loadApps();
	if(OC_User::login($_POST["user"], $_POST["password"])) {
		header("Location: ".$WEBROOT.'/'.OC_Appconfig::getValue("core", "defaultpage", "files/index.php"));
		if(!empty($_POST["remember_login"])){
			OC_User::setUsernameInCookie($_POST["user"]);
		}
		else {
			OC_User::unsetUsernameInCookie();
		}
		exit();
	}
	else {
		if(isset($_COOKIE["username"])){
			OC_Template::printGuestPage("", "login", array("error" => true, "username" => $_COOKIE["username"]));
		}else{
			OC_Template::printGuestPage("", "login", array("error" => true));
		}
	}
}

// For all others cases, we display the guest page :
else {
	OC_App::loadApps();
	if(isset($_COOKIE["username"])){
		OC_Template::printGuestPage("", "login", array("error" => false, "username" => $_COOKIE["username"]));
	}else{
		OC_Template::printGuestPage("", "login", array("error" => false));
	}
}

?>