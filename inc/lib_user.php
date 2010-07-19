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



if ( !$CONFIG_INSTALLED ) {
	$_SESSION['user_id'] = false;
	$_SESSION['username'] = '';
	$_SESSION['username_clean'] = '';
}

// Cache the userid's an groupid's
if ( !isset($_SESSION['user_id_cache']) ) {
	$_SESSION['user_id_cache'] = array();
}
if ( !isset($_SESSION['group_id_cache']) ) {
	$_SESSION['group_id_cache'] = array();
}



/**
 * Class for user management
 *
 */
abstract class OC_USER_ABSTRACT {

	/**
	 * Check if the login button is pressed and logg the user in
	 *
	 */
	abstract public static function loginLisener();

	/**
	 * Try to create a new user
	 *
	 */
	abstract public static function createUser($username, $password);

	/**
	 * Try to login a user
	 *
	 */
	abstract public static function login($username, $password);

	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	abstract public static function logoutLisener();

	/**
	 * Check if a user is logged in
	 *
	 */
	abstract public static function isLoggedIn();

	/**
	 * Try to create a new group
	 *
	 */
	abstract public static function createGroup($groupName);

	/**
	 * Get the ID of a user
	 *
	 */
	abstract public static function getUserId($username, $noCache=false);

	/**
	 * Get the ID of a group
	 *
	 */
	abstract public static function getGroupId($groupName, $noCache=false);

	/**
	 * Get the name of a group
	 *
	 */
	abstract public static function getGroupName($groupId, $noCache=false);

	/**
	 * Check if a user belongs to a group
	 *
	 */
	abstract public static function inGroup($username, $groupName);

	/**
	 * Add a user to a group
	 *
	 */
	abstract public static function addToGroup($username, $groupName);

	abstract public static function generatePassword();

	/**
	 * Get all groups the user belongs to
	 *
	 */
	abstract public static function getUserGroups($username);

	/**
	 * Set the password of a user
	 *
	 */
	abstract public static function setPassword($username, $password);

	/**
	 * Check the password of a user
	 *
	 */
	abstract public static function checkPassword($username, $password);

}
