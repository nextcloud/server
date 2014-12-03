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
 * @deprecated Use \OC_User_Backend::NOT_IMPLEMENTED instead
 */
define('OC_USER_BACKEND_NOT_IMPLEMENTED',   -501);

/**
 * actions that user backends can define
 */
/** @deprecated Use \OC_User_Backend::CREATE_USER instead */
define('OC_USER_BACKEND_CREATE_USER',       1 << 0);
/** @deprecated Use \OC_User_Backend::SET_PASSWORD instead */
define('OC_USER_BACKEND_SET_PASSWORD',      1 << 4);
/** @deprecated Use \OC_User_Backend::CHECK_PASSWORD instead */
define('OC_USER_BACKEND_CHECK_PASSWORD',    1 << 8);
/** @deprecated Use \OC_User_Backend::GET_HOME instead */
define('OC_USER_BACKEND_GET_HOME',          1 << 12);
/** @deprecated Use \OC_User_Backend::GET_DISPLAYNAME instead */
define('OC_USER_BACKEND_GET_DISPLAYNAME',   1 << 16);
/** @deprecated Use \OC_User_Backend::SET_DISPLAYNAME instead */
define('OC_USER_BACKEND_SET_DISPLAYNAME',   1 << 20);
/** @deprecated Use \OC_User_Backend::PROVIDE_AVATAR instead */
define('OC_USER_BACKEND_PROVIDE_AVATAR',    1 << 24);
/** @deprecated Use \OC_User_Backend::COUNT_USERS instead */
define('OC_USER_BACKEND_COUNT_USERS',       1 << 28);

/**
 * Abstract base class for user management. Provides methods for querying backend
 * capabilities.
 *
 * Subclass this for your own backends, and see OC_User_Example for descriptions
 */
abstract class OC_User_Backend implements OC_User_Interface {
	/**
	 * error code for functions not provided by the user backend
	 */
	const NOT_IMPLEMENTED = -501;

	/**
	 * actions that user backends can define
	 */
	const CREATE_USER		= 1;			// 1 << 0
	const SET_PASSWORD		= 16;			// 1 << 4
	const CHECK_PASSWORD	= 256;			// 1 << 8
	const GET_HOME			= 4096;			// 1 << 12
	const GET_DISPLAYNAME	= 65536;		// 1 << 16
	const SET_DISPLAYNAME	= 1048576;		// 1 << 20
	const PROVIDE_AVATAR	= 16777216;		// 1 << 24
	const COUNT_USERS		= 268435456;	// 1 << 28

	protected $possibleActions = array(
		self::CREATE_USER => 'createUser',
		self::SET_PASSWORD => 'setPassword',
		self::CHECK_PASSWORD => 'checkPassword',
		self::GET_HOME => 'getHome',
		self::GET_DISPLAYNAME => 'getDisplayName',
		self::SET_DISPLAYNAME => 'setDisplayName',
		self::PROVIDE_AVATAR => 'canChangeAvatar',
		self::COUNT_USERS => 'countUsers',
	);

	/**
	* Get all supported actions
	* @return int bitwise-or'ed actions
	*
	* Returns the supported actions as int to be
	* compared with self::CREATE_USER etc.
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
	* Check if backend implements actions
	* @param int $actions bitwise-or'ed actions
	* @return boolean
	*
	* Returns the supported actions as int to be
	* compared with self::CREATE_USER etc.
	*/
	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	/**
	 * delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser( $uid ) {
		return false;
	}

	/**
	* Get a list of all users
	* @return array an array of all uids
	*
	* Get a list of all users.
	*/
	public function getUsers($search = '', $limit = null, $offset = null) {
		return array();
	}

	/**
	* check if a user exists
	* @param string $uid the username
	* @return boolean
	*/
	public function userExists($uid) {
		return false;
	}

	/**
	* get the user's home directory
	* @param string $uid the username
	* @return boolean
	*/
	public function getHome($uid) {
		return false;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		return $uid;
	}

	/**
	 * Get a list of all display names
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
	 * Check if a user list is available or not
	 * @return boolean if users can be listed or not
	 */
	public function hasUserListings() {
		return false;
	}
}
