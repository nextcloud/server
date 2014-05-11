<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Dominik Schmidt
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 * @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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
 * error code for functions not provided by the user backend
 */
define('OC_USER_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
define('OC_USER_BACKEND_CREATE_USER',       0x00000001);
define('OC_USER_BACKEND_SET_PASSWORD',      0x00000010);
define('OC_USER_BACKEND_CHECK_PASSWORD',    0x00000100);
define('OC_USER_BACKEND_GET_HOME',			0x00001000);
define('OC_USER_BACKEND_GET_DISPLAYNAME',	0x00010000);
define('OC_USER_BACKEND_SET_DISPLAYNAME',	0x00100000);
define('OC_USER_BACKEND_PROVIDE_AVATAR',	0x01000000);
define('OC_USER_BACKEND_COUNT_USERS',		0x10000000);
//more actions cannot be defined without breaking 32bit platforms!

/**
 * Abstract base class for user management. Provides methods for querying backend
 * capabilities.
 *
 * Subclass this for your own backends, and see OC_User_Example for descriptions
 */
abstract class OC_User_Backend implements OC_User_Interface {

	protected $possibleActions = array(
		OC_USER_BACKEND_CREATE_USER => 'createUser',
		OC_USER_BACKEND_SET_PASSWORD => 'setPassword',
		OC_USER_BACKEND_CHECK_PASSWORD => 'checkPassword',
		OC_USER_BACKEND_GET_HOME => 'getHome',
		OC_USER_BACKEND_GET_DISPLAYNAME => 'getDisplayName',
		OC_USER_BACKEND_SET_DISPLAYNAME => 'setDisplayName',
		OC_USER_BACKEND_PROVIDE_AVATAR => 'canChangeAvatar',
		OC_USER_BACKEND_COUNT_USERS => 'countUsers',
	);

	/**
	* @brief Get all supported actions
	* @return int bitwise-or'ed actions
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function getSupportedActions() {
		$actions = 0;
		foreach($this->possibleActions AS $action => $methodName) {
			if(method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	/**
	* @brief Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with OC_USER_BACKEND_CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	/**
	 * @brief delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser( $uid ) {
		return false;
	}

	/**
	* @brief Get a list of all users
	* @return array an array of all uids
	*
	* Get a list of all users.
	*/
	public function getUsers($search = '', $limit = null, $offset = null) {
		return array();
	}

	/**
	* @brief check if a user exists
	* @param string $uid the username
	* @return boolean
	*/
	public function userExists($uid) {
		return false;
	}

	/**
	* @brief get the user's home directory
	* @param string $uid the username
	* @return boolean
	*/
	public function getHome($uid) {
		return false;
	}

	/**
	 * @brief get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		return $uid;
	}

	/**
	 * @brief Get a list of all display names
	 * @return array an array of  all displayNames (value) and the corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$displayNames = array();
		$users = $this->getUsers($search, $limit, $offset);
		foreach ( $users as $user) {
			$displayNames[$user] = $user;
		}
		return $displayNames;
	}

	/**
	 * @brief Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 */
	public function hasUserListings() {
		return false;
	}
}
