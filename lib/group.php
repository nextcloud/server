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

/**
 * This class provides all methods needed for managing groups.
 */
class OC_GROUP {
	// The backend used for user management
	private static $_backend;

	// Backends available (except database)
	private static $_backends = array();

	/**
	 * @brief registers backend
	 * @param $name name of the backend
	 * @returns true/false
	 *
	 * Makes a list of backends that can be used by other modules
	 */
	public static function registerBackend( $name ){
		self::$_backends[] = $name;
		return true;
	}

	/**
	 * @brief gets available backends
	 * @returns array of backends
	 *
	 * Returns the names of all backends.
	 */
	public static function getBackends(){
		return self::$_backends;
	}

	/**
	 * @brief set the group backend
	 * @param  string  $backend  The backend to use for user managment
	 * @returns true/false
	 */
	public static function setBackend( $backend = 'database' ){
		// You'll never know what happens
		if( null === $backend OR !is_string( $backend )){
			$backend = 'database';
		}

		// Load backend
		switch( $backend ){
			case 'database':
			case 'mysql':
			case 'sqlite':
				oc_require_once('Group/database.php');
				self::$_backend = new OC_GROUP_DATABASE();
				break;
			default:
				$className = 'OC_GROUP_' . strToUpper($backend);
				self::$_backend = new $className();
				break;
		}
	}

	/**
	 * Check if a user belongs to a group
	 *
	 * @param  string  $username   Name of the user to check
	 * @param  string  $groupName  Name of the group
	 */
	public static function inGroup($username, $groupName) {
		return self::$_backend->inGroup($username, $groupName);
	}

	/**
	 * Add a user to a group
	 *
	 * @param  string  $username   Name of the user to add to group
	 * @param  string  $groupName  Name of the group in which add the user
	 */
	public static function addToGroup($username, $groupName) {
		return self::$_backend->addToGroup($username, $groupName);
	}

	/**
	 * Remove a user from a group
	 *
	 * @param  string  $username   Name of the user to remove from group
	 * @param  string  $groupName  Name of the group from which remove the user
	 */
	public static function removeFromGroup($username,$groupName){
		return self::$_backend->removeFromGroup($username, $groupName);
	}

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	public static function getUserGroups($username) {
		return self::$_backend->getUserGroups($username);
	}

	/**
	 * get a list of all groups
	 *
	 */
	public static function getGroups() {
		return self::$_backend->getGroups();
	}
}
