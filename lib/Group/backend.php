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
 * Base class for user management
 *
 */
abstract class OC_GROUP_BACKEND {
	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	abstract public static function createGroup($groupName);

	/**
	 * Check if a user belongs to a group
	 *
	 * @param  string  $username   Name of the user to check
	 * @param  string  $groupName  Name of the group
	 */
	abstract public static function inGroup($username, $groupName);

	/**
	 * Add a user to a group
	 *
	 * @param  string  $username   Name of the user to add to group
	 * @param  string  $groupName  Name of the group in which add the user
	 */
	abstract public static function addToGroup($username, $groupName);

	/**
	 * Remove a user from a group
	 *
	 * @param  string  $username   Name of the user to remove from group
	 * @param  string  $groupName  Name of the group from which remove the user
	 */
	abstract public static function removeFromGroup($username,$groupName);

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	abstract public static function getUserGroups($username);

	/**
	 * get a list of all groups
	 *
	 */
	abstract public static function getGroups();
}
