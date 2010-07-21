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

global $CONFIG_BACKEND;



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

OC_USER::setBackend($CONFIG_BACKEND);



/**
 * Class for User Management
 *
 */
class OC_USER {

	// The backend used for user management
	private static $_backend;

	/**
	* Set the User Authentication Module
	*/
	public static function setBackend($backend='database') {
		if ( (null === $backend) OR (!is_string($backend)) ) {
			$backend = 'database';
		}

		switch ( $backend ) {
			case 'mysql':
			case 'sqlite':
				oc_require_once('inc/User/database.php');
				self::$_backend = new OC_USER_DATABASE();
				break;
			case 'ldap':
				oc_require_once('inc/User/ldap.php');
				self::$_backend = new OC_USER_LDAP();
				break;
			default:
				oc_require_once('inc/User/database.php');
				self::$_backend = new OC_USER_DATABASE();
				break;
		}
	}

	/**
	* check if the login button is pressed and logg the user in
	*
	*/
	public static function loginLisener() {
		return self::$_backend->loginLisener();
	}

	/**
	* try to create a new user
	*
	*/
	public static function createUser($username, $password) {
		return self::$_backend->createUser($username, $password);
	}

	/**
	* try to login a user
	*
	*/
	public static function login($username, $password) {
		return self::$_backend->login($username, $password);
	}

	/**
	* check if the logout button is pressed and logout the user
	*
	*/
	public static function logoutLisener() {
		return self::$_backend->logoutLisener();
	}

	/**
	* check if a user is logged in
	*
	*/
	public static function isLoggedIn() {
		return self::$_backend->isLoggedIn();
	}

	/**
	* try to create a new group
	*
	*/
	public static function createGroup($groupName) {
		return self::$_backend->createGroup($groupName);
	}

	/**
	* get the id of a user
	*
	*/
	public static function getUserId($username, $noCache=false) {
		return self::$_backend->getUserId($username, $noCache=false);
	}

	/**
	* get the id of a group
	*
	*/
	public static function getGroupId($groupName, $noCache=false) {
		return self::$_backend->getGroupId($groupName, $noCache=false);
	}

	/**
	* get the name of a group
	*
	*/
	public static function getGroupName($groupId, $noCache=false) {
		return self::$_backend->getGroupName($groupId, $noCache=false);
	}

	/**
	* check if a user belongs to a group
	*
	*/
	public static function inGroup($username, $groupName) {
		return self::$_backend->inGroup($username, $groupName);
	}

	/**
	* add a user to a group
	*
	*/
	public static function addToGroup($username, $groupName) {
		return self::$_backend->addToGroup($username, $groupName);
	}

	public static function generatePassword() {
		return uniqId();
	}

	/**
	* get all groups the user belongs to
	*
	*/
	public static function getUserGroups($username) {
		return self::$_backend->getUserGroups($username);
	}

	/**
	* set the password of a user
	*
	*/
	public static function setPassword($username, $password) {
		return self::$_backend->setPassword($username, $password);
	}

	/**
	* check the password of a user
	*
	*/
	public static function checkPassword($username, $password) {
		return self::$_backend->checkPassword($username, $password);
	}

}
