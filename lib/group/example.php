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
 * abstract reference class for group management
 * this class should only be used as a reference for method signatures and their descriptions
 */
abstract class OC_Group_Example {
	/**
	 * @brief Try to create a new group
	 * @param $gid The name of the group to create
	 * @returns true/false
	 *
	 * Trys to create a new group. If the group name already exists, false will
	 * be returned.
	 */
	public static function createGroup($gid){}

	/**
	 * @brief delete a group
	 * @param $gid gid of the group to delete
	 * @returns true/false
	 *
	 * Deletes a group and removes it from the group_user-table
	 */
	public static function deleteGroup($gid){}

	/**
	 * @brief is user in group?
	 * @param $uid uid of the user
	 * @param $gid gid of the group
	 * @returns true/false
	 *
	 * Checks whether the user is member of a group or not.
	 */
	public static function inGroup($uid, $gid){}

	/**
	 * @brief Add a user to a group
	 * @param $uid Name of the user to add to group
	 * @param $gid Name of the group in which add the user
	 * @returns true/false
	 *
	 * Adds a user to a group.
	 */
	public static function addToGroup($uid, $gid){}

	/**
	 * @brief Removes a user from a group
	 * @param $uid NameUSER of the user to remove from group
	 * @param $gid Name of the group from which remove the user
	 * @returns true/false
	 *
	 * removes the user from a group.
	 */
	public static function removeFromGroup($uid,$gid){}

	/**
	 * @brief Get all groups a user belongs to
	 * @param $uid Name of the user
	 * @returns array with group names
	 *
	 * This function fetches all groups a user belongs to. It does not check
	 * if the user exists at all.
	 */
	public static function getUserGroups($uid){}

	/**
	 * @brief get a list of all groups
	 * @returns array with group names
	 *
	 * Returns a list with all groups
	 */
	public static function getGroups(){}

	/**
	 * @brief get a list of all users in a group
	 * @returns array with user ids
	 */
	public static function usersInGroup($gid){}

}
