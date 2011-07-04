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

require_once(dirname(__FILE__).'/lib/base.php');
require_once('appconfig.php');
require_once('template.php');

OC_UTIL::addScript('setup');

$not_installed = !OC_CONFIG::getValue('installed', false);
$install_called = (isset($_POST['install']) AND $_POST['install']=='true');

// First step : check if the server is correctly configured for ownCloud :
$errors = OC_UTIL::checkServer();
if(count($errors) > 0) {
	OC_TEMPLATE::printGuestPage("", "error", array("errors" => $errors));
}

// Setup required :
elseif($not_installed OR $install_called) {
	require_once('setup.php');
}

// Someone is logged in :
elseif(OC_USER::isLoggedIn()) {
	if(isset($_GET["logout"]) and ($_GET["logout"])) {
		OC_USER::logout();
		header("Location: ".$WEBROOT.'/');
		exit();
	}
	else {
		header("Location: ".$WEBROOT.'/'.OC_APPCONFIG::getValue("core", "defaultpage", "files/index.php"));
		exit();
	}
}

// Someone wants to log in :
elseif(isset($_POST["user"])) {
	OC_APP::loadApps();
	if(OC_USER::login($_POST["user"], $_POST["password"])) {
		header("Location: ".$WEBROOT.'/'.OC_APPCONFIG::getValue("core", "defaultpage", "files/index.php"));
		exit();
	}
	else {
		OC_TEMPLATE::printGuestPage("", "login", array("error" => true));
	}
}

// For all others cases, we display the guest page :
else {
	OC_APP::loadApps();
	OC_TEMPLATE::printGuestPage("", "login", array("error" => false));
}

?>