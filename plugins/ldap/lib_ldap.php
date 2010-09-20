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

oc_require_once('inc/User/backend.php');



/**
 * Class for user management
 *
 */
class OC_USER_LDAP extends OC_USER_BACKEND {

	/**
	 * Check if the login button is pressed and log the user in
	 *
	 */
	public static function loginLisener() {
		return('');
	}

	/**
	 * Try to create a new user
	 *
	 * @param  string  $username  The username of the user to create
	 * @param  string  $password  The password of the new user
	 */
	public static function createUser($username, $password) {
		return false;
	}

	/**
	 * Try to login a user
	 *
	 * @param  string  $username  The username of the user to log in
	 * @param  string  $password  The password of the user
	 */
	public static function login($username, $password) {
		if ( isset($_SERVER['PHP_AUTH_USER']) AND ('' != $_SERVER['PHP_AUTH_USER']) ) {
			$_SESSION['user_id'] = $_SERVER['PHP_AUTH_USER'];
			$_SESSION['username'] = $_SERVER['PHP_AUTH_USER'];
			$_SESSION['username_clean'] = $_SERVER['PHP_AUTH_USER'];
			return true;
		}
		return false;
	}

	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	public static function logoutLisener() {
		if ( isset($_GET['logoutbutton']) AND isset($_SESSION['username']) ) {
			header('WWW-Authenticate: Basic realm="ownCloud"');
			header('HTTP/1.0 401 Unauthorized');
			die('401 Unauthorized');
		}
	}

	/**
	 * Check if the user is logged in
	 *
	 */
	public static function isLoggedIn(){
		if ( isset($_SESSION['user_id']) AND $_SESSION['user_id'] ) {
			return true;
		} else {
			if ( isset($_SERVER['PHP_AUTH_USER']) AND ('' != $_SERVER["PHP_AUTH_USER"]) ) {
				$_SESSION['user_id'] = $_SERVER['PHP_AUTH_USER'];
				$_SESSION['username'] = $_SERVER['PHP_AUTH_USER'];
				$_SESSION['username_clean'] = $_SERVER['PHP_AUTH_USER'];
				return true;
			}
		}
		return false;
	}

	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	public static function createGroup($groupName) {
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}

	/**
	 * Get the ID of a user
	 *
	 * @param  string   $username  Name of the user to find the ID
	 * @param  boolean  $noCache   If false the cache is used to find the ID
	 */
	public static function getUserId($username, $noCache=false) {
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}

	/**
	 * Get the ID of a group
	 *
	 * @param  string   $groupName  Name of the group to find the ID
	 * @param  boolean  $noCache    If false the cache is used to find the ID
	 */
	public static function getGroupId($groupName, $noCache=false) {
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}

	/**
	 * Get the name of a group
	 *
	 * @param  string  $groupId  ID of the group
	 * @param  boolean $noCache  If false the cache is used to find the name of the group
	 */
	public static function getGroupName($groupId, $noCache=false) {
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}

	/**
	 * Check if a user belongs to a group
	 *
	 * @param  string  $username   Name of the user to check
	 * @param  string  $groupName  Name of the group
	 */
	public static function inGroup($username, $groupName) {
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}

	/**
	 * Add a user to a group
	 *
	 * @param  string  $username   Name of the user to add to group
	 * @param  string  $groupName  Name of the group in which add the user
	 */
	public static function addToGroup($username, $groupName) {
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}

	/**
	 * Remove a user from a group
	 *
	 * @param  string  $username   Name of the user to remove from group
	 * @param  string  $groupName  Name of the group from which remove the user
	 */
	public static function removeFromGroup($username,$groupName){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}

	/**
	 * Generate a random password
	 */	
	public static function generatePassword() {
		return uniqId();
	}

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	public static function getUserGroups($username) {
		// does not work with MOD_AUTH (only or some modules)
		$groups=array();
		return $groups;
	}

	/**
	 * Set the password of a user
	 *
	 * @param  string  $username  User who password will be changed
	 * @param  string  $password  The new password for the user
	 */
	public static function setPassword($username, $password) {
		return false;
	}

	/**
	 * Check if the password of the user is correct
	 *
	 * @param  string  $username  Name of the user
	 * @param  string  $password  Password of the user
	 */
	public static function checkPassword($username, $password) {
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}

	/**
	 * get a list of all users
	 *
	 */
	public static function getUsers(){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
	
	/**
	 * get a list of all groups
	 *
	 */
	public static function getGroups(){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
}
