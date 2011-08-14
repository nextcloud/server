<?php

/**
* ownCloud
*
* @author Hans Bakker
* @copyright 2011 Hans Bakker hansmbakker+kde@gmail.com
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

header("Content-Type: application/jsonrequest");

$RUNTIME_NOAPPS = TRUE; //no apps, yet

require_once('../../lib/base.php');

$not_installed = !OC_Config::getValue('installed', false);

// First step : check if the server is correctly configured for ownCloud :
$errors = OC_Util::checkServer();
if(count($errors) > 0) {
        echo json_encode(array("user_valid" => "false", "comment" => $errors));
}

// Setup required :
elseif($not_installed) {
        echo json_encode(array("user_valid" => "false", "comment" => "not_installed"));

}

// Someone wants to check a user:
elseif(isset($_GET["user"]) and isset($_GET["password"])) {
        if(OC_User::checkPassword($_GET["user"], $_GET["password"]))
		echo json_encode(array("user_valid" => "true", "comment" => ""));
	else
		echo json_encode(array("user_valid" => "false", "comment" => ""));
}

// For all others cases:
else {
        echo json_encode(array("user_valid" => "false", "comment" => "unknown"));
}

?>
