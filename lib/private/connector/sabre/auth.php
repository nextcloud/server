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
	protected function validateUserPass($username, $password) {
		if (OC_User::isLoggedIn()) {
			OC_Util::setupFS(OC_User::getUser());
			return true;
		} else {
			OC_Util::setUpFS();//login hooks may need early access to the filesystem
			if(OC_User::login($username, $password)) {
				OC_Util::setUpFS(OC_User::getUser());
				return true;
			}
			else{
				return false;
			}
		}
	}

	/**
	 * Returns information about the currently logged in username.
	 *
	 * If nobody is currently logged in, this method should return null.
	 *
	 * @return string|null
	 */
	public function getCurrentUser() {
		$user = OC_User::getUser();
		if(!$user) {
			return null;
		}
		return $user;
	}

	/**
	  * Override function here. We want to cache authentication cookies
	  * in the syncing client to avoid HTTP-401 roundtrips.
	  * If the sync client supplies the cookies, then OC_User::isLoggedIn()
	  * will return true and we can see this WebDAV request as already authenticated,
	  * even if there are no HTTP Basic Auth headers.
	  * In other case, just fallback to the parent implementation.
	  *
	  * @return bool
	  */
	public function authenticate(Sabre_DAV_Server $server, $realm) {

		if (OC_User::handleApacheAuth() || OC_User::isLoggedIn()) {
			$user = OC_User::getUser();
			OC_Util::setupFS($user);
			$this->currentUser = $user;
			return true;
		}

		return parent::authenticate($server, $realm);
    }
}
