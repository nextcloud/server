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
	// The backend used for group management
	/**
	 * @var OC_Group_Interface[]
	 */
	private static $_usedBackends = array();

	/**
	 * @brief set the group backend
	 * @param  string  $backend  The backend to use for user managment
	 * @return bool
	 */
	public static function useBackend( $backend ) {
		if($backend instanceof OC_Group_Interface) {
			self::$_usedBackends[]=$backend;
		}
	}

	/**
	 * remove all used backends
	 */
	public static function clearBackends() {
		self::$_usedBackends=array();
	}

	/**
	 * @brief Try to create a new group
	 * @param string $gid The name of the group to create
	 * @return bool
	 *
	 * Tries to create a new group. If the group name already exists, false will
	 * be returned. Basic checking of Group name
	 */
	public static function createGroup( $gid ) {
		// No empty group names!
		if( !$gid ) {
			return false;
		}
		// No duplicate group names
		if( in_array( $gid, self::getGroups())) {
			return false;
		}

		$run = true;
		OC_Hook::emit( "OC_Group", "pre_createGroup", array( "run" => &$run, "gid" => $gid ));

		if($run) {
			//create the group in the first backend that supports creating groups
			foreach(self::$_usedBackends as $backend) {
				if(!$backend->implementsActions(OC_GROUP_BACKEND_CREATE_GROUP))
					continue;

				$backend->createGroup($gid);
				OC_Hook::emit( "OC_User", "post_createGroup", array( "gid" => $gid ));

				return true;
			}
			return false;
		}else{
			return false;
		}
	}

	/**
	 * @brief delete a group
	 * @param string $gid gid of the group to delete
	 * @return bool
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public static function deleteGroup( $gid ) {
		// Prevent users from deleting group admin
		if( $gid == "admin" ) {
			return false;
		}

		$run = true;
		OC_Hook::emit( "OC_Group", "pre_deleteGroup", array( "run" => &$run, "gid" => $gid ));

		if($run) {
			//delete the group from all backends
			foreach(self::$_usedBackends as $backend) {
				if(!$backend->implementsActions(OC_GROUP_BACKEND_DELETE_GROUP))
					continue;

				$backend->deleteGroup($gid);
				OC_Hook::emit( "OC_User", "post_deleteGroup", array( "gid" => $gid ));

				return true;
			}
			return false;
		}else{
			return false;
		}
	}

	/**
	 * @brief is user in group?
	 * @param string $uid uid of the user
	 * @param string $gid gid of the group
	 * @return bool
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public static function inGroup( $uid, $gid ) {
		foreach(self::$_usedBackends as $backend) {
			if($backend->inGroup($uid, $gid)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief Add a user to a group
	 * @param string $uid Name of the user to add to group
	 * @param string $gid Name of the group in which add the user
	 * @return bool
	 *
	 * Adds a user to a group.
	 */
	public static function addToGroup( $uid, $gid ) {
		// Does the group exist?
		if( !OC_Group::groupExists($gid)) {
			return false;
		}

		// Go go go
		$run = true;
		OC_Hook::emit( "OC_Group", "pre_addToGroup", array( "run" => &$run, "uid" => $uid, "gid" => $gid ));

		if($run) {
			$success=false;

			//add the user to the all backends that have the group
			foreach(self::$_usedBackends as $backend) {
				if(!$backend->implementsActions(OC_GROUP_BACKEND_ADD_TO_GROUP))
					continue;

				if($backend->groupExists($gid)) {
					$success|=$backend->addToGroup($uid, $gid);
				}
			}
			if($success) {
				OC_Hook::emit( "OC_User", "post_addToGroup", array( "uid" => $uid, "gid" => $gid ));
			}
			return $success;
		}else{
			return false;
		}
	}

	/**
	 * @brief Removes a user from a group
	 * @param string $uid Name of the user to remove from group
	 * @param string $gid Name of the group from which remove the user
	 * @return bool
	 *
	 * removes the user from a group.
	 */
	public static function removeFromGroup( $uid, $gid ) {
		$run = true;
		OC_Hook::emit( "OC_Group", "pre_removeFromGroup", array( "run" => &$run, "uid" => $uid, "gid" => $gid ));

		if($run) {
			//remove the user from the all backends that have the group
			foreach(self::$_usedBackends as $backend) {
				if(!$backend->implementsActions(OC_GROUP_BACKEND_REMOVE_FROM_GOUP))
					continue;

				$backend->removeFromGroup($uid, $gid);
				OC_Hook::emit( "OC_User", "post_removeFromGroup", array( "uid" => $uid, "gid" => $gid ));
			}
			return true;
		}else{
			return false;
		}
	}

	/**
	 * @brief Get all groups a user belongs to
	 * @param string $uid Name of the user
	 * @return array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public static function getUserGroups( $uid ) {
		$groups=array();
		foreach(self::$_usedBackends as $backend) {
			$groups=array_merge($backend->getUserGroups($uid), $groups);
		}
		asort($groups);
		return $groups;
	}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public static function getGroups($search = '', $limit = -1, $offset = 0) {
		$groups = array();
		foreach (self::$_usedBackends as $backend) {
			$groups = array_merge($backend->getGroups($search, $limit, $offset), $groups);
		}
		asort($groups);
		return $groups;
	}

	/**
	 * check if a group exists
	 * @param string $gid
	 * @return bool
	 */
	public static function groupExists($gid) {
		foreach(self::$_usedBackends as $backend) {
			if ($backend->groupExists($gid)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public static function usersInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$users=array();
		foreach(self::$_usedBackends as $backend) {
			$users = array_merge($backend->usersInGroup($gid, $search, $limit, $offset), $users);
		}
		return $users;
	}

	/**
	 * @brief get a list of all users in several groups
	 * @param array $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with user ids
	 */
	public static function usersInGroups($gids, $search = '', $limit = -1, $offset = 0) {
		$users = array();
		foreach ($gids as $gid) {
			// TODO Need to apply limits to groups as total
			$users = array_merge(array_diff(self::usersInGroup($gid, $search, $limit, $offset), $users), $users);
		}
		return $users;
	}

	/**
	 * @brief get a list of all display names in a group
	 * @returns array with display names (value) and user ids(key)
	 */
	public static function displayNamesInGroup($gid, $search = '', $limit = -1, $offset = 0) {
		$displayNames=array();
		foreach(self::$_usedBackends as $backend) {
			if($backend->implementsActions(OC_GROUP_BACKEND_GET_DISPLAYNAME)) {
				$displayNames = array_merge($backend->displayNamesInGroup($gid, $search, $limit, $offset), $displayNames);
			} else {
				$users = $backend->usersInGroup($gid, $search, $limit, $offset);
				$names = array_combine($users, $users);
				$displayNames = array_merge($names, $displayNames);
			}
		}
		return $displayNames;
	}

	/**
	 * @brief get a list of all display names in several groups
	 * @param array $gids
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array with display names (Key) user ids (value)
	 */
	public static function displayNamesInGroups($gids, $search = '', $limit = -1, $offset = 0) {
		$displayNames = array();
		foreach ($gids as $gid) {
			// TODO Need to apply limits to groups as total
			$diff = array_diff(
				self::displayNamesInGroup($gid, $search, $limit, $offset),
				$displayNames
			);
			if ($diff) {
				$displayNames = array_merge($diff, $displayNames);
			}
		}
		return $displayNames;
	}
}
