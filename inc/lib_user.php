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

//cache the userid's an groupid's
if ( !isset($_SESSION['user_id_cache']) ) {
	$_SESSION['user_id_cache'] = array();
}
if ( !isset($_SESSION['group_id_cache']) ) {
	$_SESSION['group_id_cache'] = array();
}




/**
 * Class for User Management
 *
 */
class OC_USER {

	// The backend used for user management
	private static $_backend;

	/**
	 * Set the User Authentication Module
	 *
	 * @param  string  $backend  The backend to use for user managment
	 */
	public static function setBackend($backend='database') {
		if ( (null === $backend) OR (!is_string($backend)) ) {
			$backend = 'database';
		}

		switch ( $backend ) {
			case 'database':
			case 'mysql':
			case 'sqlite':
				oc_require_once('inc/User/database.php');
				self::$_backend = new OC_USER_DATABASE();
				break;
			default:
				$className = 'OC_USER_' . strToUpper($backend);
				self::$_backend = new $className();
				break;
		}
	}

	/**
	 * Check if the login button is pressed and log the user in
	 *
	 */
	public static function loginListener() {
		return self::$_backend->loginListener();
	}

	/**
	 * Try to create a new user
	 *
	 * @param  string  $username  The username of the user to create
	 * @param  string  $password  The password of the new user
	 */
	public static function createUser($username, $password) {
		return self::$_backend->createUser($username, $password);
	}

	/**
	 * Try to login a user
	 *
	 * @param  string  $username  The username of the user to log in
	 * @param  string  $password  The password of the user
	 */
	public static function login($username, $password) {
		return self::$_backend->login($username, $password);
	}

	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	public static function logoutListener() {
		return self::$_backend->logoutListener();
	}

	/**
	 * Check if the user is logged in
	 *
	 */
	public static function isLoggedIn() {
		return self::$_backend->isLoggedIn();
	}

	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	public static function createGroup($groupName) {
		return self::$_backend->createGroup($groupName);
	}

	/**
	 * Get the ID of a user
	 *
	 * @param  string   $username  Name of the user to find the ID
	 * @param  boolean  $noCache   If false the cache is used to find the ID
	 */
	public static function getUserId($username, $noCache=false) {
		return self::$_backend->getUserId($username, $noCache);
	}

	/**
	 * Get the ID of a group
	 *
	 * @param  string   $groupName  Name of the group to find the ID
	 * @param  boolean  $noCache    If false the cache is used to find the ID
	 */
	public static function getGroupId($groupName, $noCache=false) {
		return self::$_backend->getGroupId($groupName, $noCache);
	}

	/**
	 * Get the name of a group
	 *
	 * @param  string  $groupId  ID of the group
	 * @param  boolean $noCache  If false the cache is used to find the name of the group
	 */
	public static function getGroupName($groupId, $noCache=false) {
		return self::$_backend->getGroupName($groupId, $noCache);
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
	 * Generate a random password
	 */
	public static function generatePassword() {
		return substr(md5(uniqId().time()),0,10);
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
	 * Set the password of a user
	 *
	 * @param  string  $username  User who password will be changed
	 * @param  string  $password  The new password for the user
	 */
	public static function setPassword($username, $password) {
		return self::$_backend->setPassword($username, $password);
	}

	/**
	 * Check if the password of the user is correct
	 *
	 * @param  string  $username  Name of the user
	 * @param  string  $password  Password of the user
	 */
	public static function checkPassword($username, $password) {
		return self::$_backend->checkPassword($username, $password);
	}

	/**
	 * get a list of all users
	 *
	 */
	public static function getUsers() {
		return self::$_backend->getUsers();
	}
	
	/**
	 * get a list of all groups
	 *
	 */
	public static function getGroups() {
		return self::$_backend->getGroups();
	}
}
