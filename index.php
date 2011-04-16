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

require_once( 'lib/base.php' );
require_once( 'appconfig.php' );
require_once( 'template.php' );
var_dump( $_SESSION );
//exit;
if( OC_USER::isLoggedIn()){
	if( $_GET["logout"] ){
		OC_USER::logout();
		OC_TEMPLATE::printGuestPage( "", "logout" );
	}
	else{
		header( "Location: ".OC_APPCONFIG::getValue( "core", "defaultpage", "files/index.php" ));
		exit();
	}
}
else{
	if( OC_USER::login( $_POST["user"], $_POST["password"] )){
		header( "Location: ".OC_APPCONFIG::getValue( "core", "defaultpage", "files/index.php" ));
		exit();
	}
	else{
		$error = false;
		// Say "bad login" in case the user wanted to login
		if( $_POST["user"] && $_POST["password"] ){
			$error = true;
		}
		OC_TEMPLATE::printGuestPage( "", "login", array( "error" => $error ));
	}
}

?>
