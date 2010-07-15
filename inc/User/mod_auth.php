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
 * Class for usermanagement in a SQL Database
 * eg mysql, sqlite
 */
class OC_USER_MOD_AUTH extends OC_USER {
	
	/**
	* check if the login button is pressed and logg the user in
	*
	*/
	public static function loginLisener(){
		return('');
	}
	
	
	/**
	* try to create a new user
	*
	*/
	public static function createUser($username,$password){
		return false;
	}
	
	/**
	* try to login a user
	*
	*/
	public static function login($username,$password){
		if (isset($_SERVER["PHP_AUTH_USER"]) && $_SERVER["PHP_AUTH_USER"] != "") {
			$_SESSION['user_id']= $_SERVER["PHP_AUTH_USER"];
			$_SESSION['username']= $_SERVER["PHP_AUTH_USER"];
			$_SESSION['username_clean']= $_SERVER["PHP_AUTH_USER"];
			return true;
		}
		return false;
	}
	
	/**
	* check if the logout button is pressed and logout the user
	*
	*/
	public static function logoutLisener(){
		if(isset($_GET['logoutbutton']) && isset($_SESSION['username'])){
			header('WWW-Authenticate: Basic realm="ownCloud"');
			header('HTTP/1.0 401 Unauthorized');
			die('401 Unauthorized');
		}
	}
	
	/**
	* check if a user is logged in
	*
	*/
	public static function isLoggedIn(){
		if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
			return true;
		}
		else {
			if (isset($_SERVER["PHP_AUTH_USER"]) && $_SERVER["PHP_AUTH_USER"] != "") {
				$_SESSION['user_id']= $_SERVER["PHP_AUTH_USER"];
				$_SESSION['username']= $_SERVER["PHP_AUTH_USER"];
				$_SESSION['username_clean']= $_SERVER["PHP_AUTH_USER"];
				return true;;
			}
		}
		return false;
	}
	
	/**
	* try to create a new group
	*
	*/
	public static function createGroup($groupname){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
	
	/**
	* get the id of a user
	*
	*/
	public static function getUserId($username,$nocache=false){
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}
	
	/**
	* get the id of a group
	*
	*/
	public static function getGroupId($groupname,$nocache=false){
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}
	
	/**
	* get the name of a group
	*
	*/
	public static function getGroupName($groupid,$nocache=false){
		// does not work with MOD_AUTH (only or some modules)
		return 0;
	}
	
	/**
	* check if a user belongs to a group
	*
	*/
	public static function inGroup($username,$groupname){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
	
	/**
	* add a user to a group
	*
	*/
	public static function addToGroup($username,$groupname){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
	
	public static function generatePassword(){
		return uniqid();
	}
	
	/**
	* get all groups the user belongs to
	*
	*/
	public static function getUserGroups($username){
		// does not work with MOD_AUTH (only or some modules)
		$groups=array();
		return $groups;
	}
	
	/**
	* set the password of a user
	*
	*/
	public static function setPassword($username,$password){
		return false;
	}
	
	/**
	* check the password of a user
	*
	*/
	public static function checkPassword($username,$password){
		// does not work with MOD_AUTH (only or some modules)
		return false;
	}
}

?>