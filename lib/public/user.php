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

/**
 * Public interface of ownCloud for apps to use.
 * User Class.
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides access to the user management. You can get information
 * about the currently logged in user and the permissions for example
 */
class User {
	/**
	 * @brief get the user id of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getUser() {
		return \OC_USER::getUser();
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers($search = '', $limit = null, $offset = null) {
		return \OC_USER::getUsers();
	}

	/**
	 * @brief get the user display name of the user currently logged in.
	 * @return string display name
	 */
	public static function getDisplayName($user=null) {
		return \OC_USER::getDisplayName($user);
	}

	/**
	 * @brief Get a list of all display names
	 * @returns array with all display names (value) and the correspondig uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public static function getDisplayNames($search = '', $limit = null, $offset = null) {
		return \OC_USER::getDisplayNames($search, $limit, $offset);
	}

	/**
	 * @brief Check if the user is logged in
	 * @returns true/false
	 *
	 * Checks if the user is logged in
	 */
	public static function isLoggedIn() {
		return \OC_USER::isLoggedIn();
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @param string $excludingBackend (default none)
	 * @return boolean
	 */
	public static function userExists( $uid, $excludingBackend = null ) {
		return \OC_USER::userExists( $uid, $excludingBackend );
	}
	/**
	 * @brief Loggs the user out including all the session data
	 * Logout, destroys session
	 */
	public static function logout() {
		\OC_USER::logout();
	}

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public static function checkPassword( $uid, $password ) {
		return \OC_USER::checkPassword( $uid, $password );
	}

	/**
	* Check if the user is a admin, redirects to home if not
	*/
	public static function checkAdminUser() {
		\OC_Util::checkAdminUser();
	}

	/**
	* Check if the user is logged in, redirects to home if not. With
	* redirect URL parameter to the request URI.
	*/
	public static function checkLoggedIn() {
		\OC_Util::checkLoggedIn();
	}
}
