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
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/



/**
 * Base class for user management
 *
 */
abstract class OC_USER_BACKEND {

	/**
	 * Check if the login button is pressed and log the user in
	 *
	 */
	abstract public static function loginListener();

	/**
	 * Try to create a new user
	 *
	 * @param  string  $username  The username of the user to create
	 * @param  string  $password  The password of the new user
	 */
	abstract public static function createUser($username, $password);

	/**
	 * Try to login a user
	 *
	 * @param  string  $username  The username of the user to log in
	 * @param  string  $password  The password of the user
	 */
	abstract public static function login($username, $password);

	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	abstract public static function logoutListener();

	/**
	 * Check if some user is logged in
	 *
	 */
	abstract public static function isLoggedIn();

	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	abstract public static function createGroup($groupName);

	/**
	 * Get the ID of a user
	 *
	 * @param  string   $username  Name of the user to find the ID
	 * @param  boolean  $noCache   If false the cache is used to find the ID
	 */
	abstract public static function getUserId($username, $noCache=false);

	/**
	 * Get the ID of a group
	 *
	 * @param  string   $groupName  Name of the group to find the ID
	 * @param  boolean  $noCache    If false the cache is used to find the ID
	 */
	abstract public static function getGroupId($groupName, $noCache=false);

	/**
	 * Get the name of a group
	 *
	 * @param  string  $groupId  ID of the group
	 * @param  boolean $noCache  If false the cache is used to find the name of the group
	 */
	abstract public static function getGroupName($groupId, $noCache=false);

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
	 * Generate a random password
	 */
	abstract public static function generatePassword();

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	abstract public static function getUserGroups($username);

	/**
	 * Set the password of a user
	 *
	 * @param  string  $username  User who password will be changed
	 * @param  string  $password  The new password for the user
	 */
	abstract public static function setPassword($username, $password);

	/**
	 * Check if the password of the user is correct
	 *
	 * @param  string  $username  Name of the user
	 * @param  string  $password  Password of the user
	 */
	abstract public static function checkPassword($username, $password);


	/**
	 * get a list of all users
	 *
	 */
	abstract public static function getUsers();
	
	/**
	 * get a list of all groups
	 *
	 */
	abstract public static function getGroups();
}
