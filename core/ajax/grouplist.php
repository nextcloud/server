<?php

/**
* ownCloud - ajax group list
*
* @author Hans Bakker
* @copyright 2011 hansmbakker+kde@gmail.com
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
require_once('../../lib/base.php');

if(!OC_User::isLoggedIn()){
	if(!isset($_SERVER['PHP_AUTH_USER'])){
		header('WWW-Authenticate: Basic realm="ownCloud Server"');
		header('HTTP/1.0 401 Unauthorized');
		echo 'Valid credentials must be supplied';
		exit();
	} else {
		if(!OC_User::checkPassword($_SERVER["PHP_AUTH_USER"], $_SERVER["PHP_AUTH_PW"])){
			exit();
		}
	}
}

$groups = array();

foreach( OC_Group::getGroups() as $i ){
       	// Do some more work here soon
        $groups[] = array( "groupname" => $i );
}

OC_JSON::encodedPrint($groups);

?>
