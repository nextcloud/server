<?php

/**
 * ownCloud
 *
 * @author Jakob Sack
 * @copyright 2011 Jakob Sack kde@jakobsack.de
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

class OC_Connector_Sabre_Auth extends Sabre_DAV_Auth_Backend_AbstractBasic {
	/**
	 * Validates a username and password
	 *
	 * This method should return true or false depending on if login
	 * succeeded.
	 *
	 * @return bool
	 */
	protected function validateUserPass($username, $password){
		if (OC_User::isLoggedIn()) {
			OC_Util::setupFS($username);
			return true;
		} else {
			OC_Util::setUpFS();//login hooks may need early access to the filesystem
			if(OC_User::login($username,$password)){
				OC_Util::setUpFS($username);
				return true;
			}
			else{
				return false;
			}
		}
	}
} 
