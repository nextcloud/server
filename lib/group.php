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
 *
 * Hooks provided:
 *   pre_createGroup(&run, gid)
 *   post_createGroup(gid)
 *   pre_deleteGroup(&run, gid)
 *   post_deleteGroup(gid)
 *   pre_addToGroup(&run, uid, gid)
 *   post_addToGroup(uid, gid)
 *   pre_removeFromGroup(&run, uid, gid)
 *   post_removeFromGroup(uid, gid)
 */
class OC_Group {
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
				self::$_backend = new OC_Group_Database();
				break;
			default:
				$className = 'OC_GROUP_' . strToUpper($backend);
				self::$_backend = new $className();
				break;
		}
	}

	/**
	 * @brief Try to create a new group
	 * @param $gid The name of the group to create
	 * @returns true/false
	 *
	 * Trys to create a new group. If the group name already exists, false will
	 * be returned. Basic checking of Group name
	 *
	 * Allowed characters in the username are: "a-z", "A-Z", "0-9" and "_.@-"
	 */
	public static function createGroup( $gid ){
		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9" and "_.@-"
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $gid )){
			return false;
		}
		// No empty group names!
		if( !$gid ){
			return false;
		}
		// No duplicate group names
		if( in_array( $gid, self::getGroups())){
			return false;
		}

		$run = true;
		OC_Hook::emit( "OC_Group", "pre_createGroup", array( "run" => &$run, "gid" => $gid ));

		if( $run && self::$_backend->createGroup( $gid )){
			OC_Hook::emit( "OC_Group", "post_createGroup", array( "gid" => $gid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief delete a group
	 * @param $gid gid of the group to delete
	 * @returns true/false
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public static function deleteGroup( $gid ){
		// Prevent users from deleting group admin
		if( $gid == "admin" ){
			return false;
		}

		$run = true;
		OC_Hook::emit( "OC_Group", "pre_deleteGroup", array( "run" => &$run, "gid" => $gid ));

		if( $run && self::$_backend->deleteGroup( $gid )){
			OC_Hook::emit( "OC_Group", "post_deleteGroup", array( "gid" => $gid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief is user in group?
	 * @param $uid uid of the user
	 * @param $gid gid of the group
	 * @returns true/false
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public static function inGroup( $uid, $gid ){
		return self::$_backend->inGroup($uid, $gid);
	}

	/**
	 * @brief Add a user to a group
	 * @param $uid Name of the user to add to group
	 * @param $gid Name of the group in which add the user
	 * @returns true/false
	 *
	 * Adds a user to a group.
	 */
	public static function addToGroup( $uid, $gid ){
		// Does the user exist?
		if( !OC_User::userExists($uid)){
			return false;
		}
		// Does the group exist?
		if( !OC_Group::groupExists($gid)){
			return false;
		}

		// Go go go
		$run = true;
		OC_Hook::emit( "OC_Group", "pre_addToGroup", array( "run" => &$run, "uid" => $uid, "gid" => $gid ));

		if( $run && self::$_backend->addToGroup( $uid, $gid )){
			OC_Hook::emit( "OC_Group", "post_addToGroup", array( "uid" => $uid, "gid" => $gid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Removes a user from a group
	 * @param $uid Name of the user to remove from group
	 * @param $gid Name of the group from which remove the user
	 * @returns true/false
	 *
	 * removes the user from a group.
	 */
	public static function removeFromGroup( $uid, $gid ){
		$run = true;
		OC_Hook::emit( "OC_Group", "pre_removeFromGroup", array( "run" => &$run, "uid" => $uid, "gid" => $gid ));

		if( $run && self::$_backend->removeFromGroup( $uid, $gid )){
			OC_Hook::emit( "OC_Group", "post_removeFromGroup", array( "uid" => $uid, "gid" => $gid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Get all groups a user belongs to
	 * @param $uid Name of the user
	 * @returns array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public static function getUserGroups( $uid ){
		return self::$_backend->getUserGroups($uid);
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public static function getGroups(){
		return self::$_backend->getGroups();
	}
	
	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public static function groupExists($gid){
		return in_array( $gid, self::getGroups());
	}
	
	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public static function usersInGroup($gid){
		return self::$_backend->usersInGroup($gid);
	}
}
