<?php

/**
* ownCloud
*
* @author Frank Karlitschek
* @copyright 2012 Frank Karlitschek frank@owncloud.org
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

if (!OC::handleRequest()) {
// Not handled -> we display the login page:
	OC_App::loadApps(array('prelogin'));
	$error = false;
	// remember was checked after last login
	if (OC::tryRememberLogin()) {
		// nothing more to do

	// Someone wants to log in :
	} elseif (OC::tryFormLogin()) {
		$error = true;
	
	// The user is already authenticated using Apaches AuthType Basic... very usable in combination with LDAP
	} elseif(OC::tryBasicAuthLogin()) {
		$error = true;
	}
	if(!array_key_exists('sectoken', $_SESSION) || (array_key_exists('sectoken', $_SESSION) && is_null(OC::$REQUESTEDFILE)) || substr(OC::$REQUESTEDFILE, -3) == 'php'){
		OC_Util::displayLoginPage($error);
	}
}
