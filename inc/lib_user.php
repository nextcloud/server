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
if( !isset($_SESSION['group_id_cache']) ) {
	$_SESSION['group_id_cache'] = array();
}



/**
 * Class for user management
 *
 */
class OC_USER {
	
	public static $classType;
	
	/**
	 * Check if the login button is pressed and logg the user in
	 *
	 */
	public static function loginLisener() {
		return self::classType->loginLisener();
	}
	
	
	/**
	 * Try to create a new user
	 *
	 */
	public static function createUser($username, $password) {
		return self::classType->createUser($username, $password);
	}
	
	/**
	 * Try to login a user
	 *
	 */
	public static function login($username, $password) {
		return self::classType->login($username, $password);
	}
	
	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	public static function logoutLisener() {
		return self::classType->logoutLisener();
	}
	
	/**
	 * Check if a user is logged in
	 *
	 */
	public static function isLoggedIn() {
		return self::classType->isLoggedIn();
	}
	
	/**
	 * Try to create a new group
	 *
	 */
	public static function createGroup($groupName) {
		return self::classType->createGroup($groupName);
	}
	
	/**
	 * Get the ID of a user
	 *
	 */
	public static function getUserId($username, $noCache=false) {
		return self::classType->getUserId($username, $noCache);
	}
	
	/**
	 * Get the ID of a group
	 *
	 */
	public static function getGroupId($groupName, $noCache=false) {
		return self::classType->getGroupId($groupName, $noCache);
	}
	
	/**
	 * Get the name of a group
	 *
	 */
	public static function getGroupName($groupId, $noCache=false) {
		return self::classType->getGroupName($groupId, $noCache);
	}
	
	/**
	 * Check if a user belongs to a group
	 *
	 */
	public static function inGroup($username, $groupName) {
		return self::classType->inGroup($username, $groupName);
	}
	
	/**
	 * Add a user to a group
	 *
	 */
	public static function addToGroup($username, $groupName) {
		return self::classType->addToGroup($username, $groupName);
	}
	
	public static function generatePassword() {
		return uniqId();
	}
	
	/**
	 * Get all groups the user belongs to
	 *
	 */
	public static function getUserGroups($username) {
		return self::classType->getUserGroups($username);
	}
	
	/**
	 * Set the password of a user
	 *
	 */
	public static function setPassword($username, $password) {
		return self::classType->setPassword($username, $password);
	}
	
	/**
	 * Check the password of a user
	 *
	 */
	public static function checkPassword($username, $password) {
		return self::classType->checkPassword($username, $password);
	}
}



/**
 * Funtion to set the User Authentication Module
 */
function set_OC_USER() {
	global $CONFIG_BACKEND;

	if ( isset($CONFIG_BACKEND) ) {
		switch( $CONFIG_BACKEND ) {
			case 'mysql':
			case 'sqlite':
				require_once 'User/database.php';
				self::classType = new OC_USER_Database();
				break;
			case 'ldap':
				require_once 'User/ldap.php';
				self::classType = new OC_USER_LDAP();
				break;
			default:
				require_once 'User/database.php';
				self::classType = new OC_USER_Database();
				break;
		}
	} else {
		require_once 'User/database.php';
		self::classType = new OC_USER_Database();
	}
}



set_OC_USER();
