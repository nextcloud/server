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
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 *
 */
class OC_USER_DATABASE extends OC_USER_BACKEND {

	/**
	 * Check if the login button is pressed and log the user in
	 *
	 */
	public static function loginLisener(){
		if ( isset($_POST['loginbutton']) AND isset($_POST['password']) AND isset($_POST['login']) ) {
			if ( OC_USER::login($_POST['login'], $_POST['password']) ) {
				echo 1;
				OC_LOG::event($_SESSION['username'], 1, '');
				echo 2;
				if ( (isset($CONFIG_HTTPFORCESSL) AND $CONFIG_HTTPFORCESSL) 
				     OR (isset($_SERVER['HTTPS']) AND ('on' == $_SERVER['HTTPS'])) ) {
					$url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				} else {
					$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				}
				header("Location: $url");
				die();
			} else {
				return('error');
			}
		}
		return('');
	}

	/**
	 * Try to create a new user
	 *
	 * @param  string  $username  The username of the user to create
	 * @param  string  $password  The password of the new user
	 */
	public static function createUser($username, $password) {
		global $CONFIG_DBTABLEPREFIX;

		// Check if the user already exists
		if ( 0 != OC_USER::getUserId($username, true) ) {
			return false;
		} else {
			$usernameClean = strToLower($username);
			$password = sha1($password);
			$username = OC_DB::escape($username);
			$usernameClean = OC_DB::escape($usernameClean);
			$query = "INSERT INTO  `{$CONFIG_DBTABLEPREFIX}users` (`user_name` ,`user_name_clean` ,`user_password`) "
			       . "VALUES ('$username',  '$usernameClean',  '$password')";
			$result = OC_DB::query($query);
			return $result ? true : false;
		}
	}

	/**
	 * Try to login a user
	 *
	 * @param  string  $username  The username of the user to log in
	 * @param  string  $password  The password of the user
	 */
	public static function login($username,$password){
		global $CONFIG_DBTABLEPREFIX;

		$password = sha1($password);
		$usernameClean = strtolower($username);
		$username = OC_DB::escape($username);
		$usernameClean = OC_DB::escape($usernameClean);
		$query = "SELECT user_id FROM {$CONFIG_DBTABLEPREFIX}users "
		       . "WHERE user_name_clean = '$usernameClean' AND  user_password =  '$password' LIMIT 1";
		$result = OC_DB::select($query);
		if ( isset($result[0]) AND isset($result[0]['user_id']) ) {
			$_SESSION['user_id'] = $result[0]['user_id'];
			$_SESSION['username'] = $username;
			$_SESSION['username_clean'] = $usernameClean;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the logout button is pressed and logout the user
	 *
	 */
	public static function logoutLisener() {
		if ( isset($_GET['logoutbutton']) AND isset($_SESSION['username']) ) {
			OC_LOG::event($_SESSION['username'], 2, '');
			$_SESSION['user_id'] = false;
			$_SESSION['username'] = '';
			$_SESSION['username_clean'] = '';
		}
	}

	/**
	 * Check if the user is logged in
	 *
	 */
	public static function isLoggedIn() {
		if ( isset($_SESSION['user_id']) AND $_SESSION['user_id'] ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Try to create a new group
	 *
	 * @param  string  $groupName  The name of the group to create
	 */
	public static function createGroup($groupName) {
		global $CONFIG_DBTABLEPREFIX;

		if ( 0 == OC_USER::getGroupId($groupName, true) ) {
			$groupName = OC_DB::escape($groupName);
			$query = "INSERT INTO  `{$CONFIG_DBTABLEPREFIX}groups` (`group_name`) VALUES ('$groupName')";
			$result = OC_DB::query($query);
			return $result ? true : false;
		} else {
			return false;
		}
	}

	/**
	 * Get the ID of a user
	 *
	 * @param  string   $username  Name of the user to find the ID
	 * @param  boolean  $noCache   If false the cache is used to find the ID
	 */
	public static function getUserId($username, $noCache=false) {
		global $CONFIG_DBTABLEPREFIX;

		$usernameClean = strToLower($username);
		// Try to use cached value to avoid an SQL query
		if ( !$noCache AND isset($_SESSION['user_id_cache'][$usernameClean]) ) {
			return $_SESSION['user_id_cache'][$usernameClean];
		}
		$usernameClean = OC_DB::escape($usernameClean);
		$query = "SELECT user_id FROM {$CONFIG_DBTABLEPREFIX}users WHERE user_name_clean = '$usernameClean'";
		$result = OC_DB::select($query);
		if ( !is_array($result) ) {
			return 0;
		}
		if ( isset($result[0]) AND isset($result[0]['user_id']) ) {
			$_SESSION['user_id_cache'][$usernameClean] = $result[0]['user_id'];
			return $result[0]['user_id'];
		} else {
			return 0;
		}
	}

	/**
	 * Get the ID of a group
	 *
	 * @param  string   $groupName  Name of the group to find the ID
	 * @param  boolean  $noCache    If false the cache is used to find the ID
	 */
	public static function getGroupId($groupName, $noCache=false) {
		global $CONFIG_DBTABLEPREFIX;

		// Try to use cached value to avoid an SQL query
		if ( !$noCache AND isset($_SESSION['group_id_cache'][$groupName]) ) {
			return $_SESSION['group_id_cache'][$groupName];
		}
		$groupName = OC_DB::escape($groupName);
		$query = "SELECT group_id FROM {$CONFIG_DBTABLEPREFIX}groups WHERE group_name = '$groupName'";
		$result = OC_DB::select($query);
		if ( !is_array($result) ) {
			return 0;
		}
		if ( isset($result[0]) AND isset($result[0]['group_id']) ){
			$_SESSION['group_id_cache'][$groupName] = $result[0]['group_id'];
			return $result[0]['group_id'];
		} else {
			return 0;
		}
	}

	/**
	 * Get the name of a group
	 *
	 * @param  string  $groupId  ID of the group
	 * @param  boolean $noCache  If false the cache is used to find the name of the group
	 */
	public static function getGroupName($groupId, $noCache=false) {
		global $CONFIG_DBTABLEPREFIX;

		// Try to use cached value to avoid an sql query
		if ( !$noCache AND ($name = array_search($groupId, $_SESSION['group_id_cache'])) ) {
			return $name;
		}
		$groupId = (integer)$groupId;
		$query = "SELECT group_name FROM {$CONFIG_DBTABLEPREFIX}groups WHERE group_id = '$groupId' LIMIT 1";
		$result = OC_DB::select($query);
		if ( isset($result[0]) AND isset($result[0]['group_name']) ) {
			return $result[0]['group_name'];
		} else {
			return 0;
		}
	}

	/**
	 * Check if a user belongs to a group
	 *
	 * @param  string  $username   Name of the user to check
	 * @param  string  $groupName  Name of the group
	 */
	public static function inGroup($username,$groupName) {
		global $CONFIG_DBTABLEPREFIX;

		$userId = OC_USER::getuserid($username);
		$groupId = OC_USER::getgroupid($groupName);
		if ( ($groupId > 0) AND ($userId > 0) ) {
			$query = "SELECT * FROM  {$CONFIG_DBTABLEPREFIX}user_group WHERE group_id = '$groupId' AND user_id = '$userId';";
			$result = OC_DB::select($query);
			if ( isset($result[0]) AND isset($result[0]['user_group_id']) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Add a user to a group
	 *
	 * @param  string  $username   Name of the user to add to group
	 * @param  string  $groupName  Name of the group in which add the user
	 */
	public static function addToGroup($username, $groupName) {
		global $CONFIG_DBTABLEPREFIX;

		if ( !OC_USER::inGroup($username, $groupName) ) {
			$userId = OC_USER::getUserId($username);
			$groupId = OC_USER::getGroupId($groupName);
			if ( (0 != $groupId) AND (0 != $userId) ) {
				$query = "INSERT INTO `{$CONFIG_DBTABLEPREFIX}user_group` (`user_id` ,`group_id`) VALUES ('$userId',  '$groupId');";
				$result = OC_DB::query($query);
				if ( $result ) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Generate a random password
	 */
	public static function generatePassword(){
		return uniqId();
	}

	/**
	 * Get all groups the user belongs to
	 *
	 * @param  string  $username  Name of the user
	 */
	public static function getUserGroups($username) {
		global $CONFIG_DBTABLEPREFIX;

		$userId = OC_USER::getUserId($username);
		$query = "SELECT group_id FROM {$CONFIG_DBTABLEPREFIX}user_group WHERE user_id = '$userId'";
		$result = OC_DB::select($query);
		$groups = array();
		if ( is_array($result) ) {
			foreach ( $result as $group ) {
				$groupId = $group['group_id'];
				$groups[] = OC_USER::getGroupName($groupId);
			}
		}
		return $groups;
	}

	/**
	 * Set the password of a user
	 *
	 * @param  string  $username  User who password will be changed
	 * @param  string  $password  The new password for the user
	 */
	public static function setPassword($username, $password) {
		global $CONFIG_DBTABLEPREFIX;

		$password = sha1($password);
		$userId = OC_USER::getUserId($username);
		$query = "UPDATE {$CONFIG_DBTABLEPREFIX}users SET user_password = '$password' WHERE user_id ='$userId'";
		$result = OC_DB::query($query);
		if ( $result ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the password of the user is correct
	 *
	 * @param  string  $username  Name of the user
	 * @param  string  $password  Password of the user
	 */
	public static function checkPassword($username, $password) {
		global $CONFIG_DBTABLEPREFIX;

		$password = sha1($password);
		$usernameClean = strToLower($username);
		$usernameClean = OC_DB::escape($usernameClean);
		$username = OC_DB::escape($username);
		$query = "SELECT user_id FROM '{$CONFIG_DBTABLEPREFIX}users' "
		       . "WHERE user_name_clean = '$usernameClean' AND user_password =  '$password' LIMIT 1";
		$result = OC_DB::select($query);
		if ( isset($result[0]) AND isset($result[0]['user_id']) AND ($result[0]['user_id'] > 0) ) {
			return true;
		} else {
			return false;
		}
	}

}
