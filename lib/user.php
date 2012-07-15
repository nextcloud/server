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
 * This class provides all methods for user management.
 *
 * Hooks provided:
 *   pre_createUser(&run, uid, password)
 *   post_createUser(uid, password)
 *   pre_deleteUser(&run, uid)
 *   post_deleteUser(uid)
 *   pre_setPassword(&run, uid, password)
 *   post_setPassword(uid, password)
 *   pre_login(&run, uid)
 *   post_login(uid)
 *   logout()
 */
class OC_User {
	// The backend used for user management
	private static $_usedBackends = array();

	// Backends available (except database)
	private static $_backends = array();

	/**
	 * @brief registers backend
	 * @param $name name of the backend
	 * @returns true/false
	 *
	 * Makes a list of backends that can be used by other modules
	 */
	public static function registerBackend( $name ){
		self::$_backends[] = $name;
		return true;
	}

	/**
	 * @brief gets available backends
	 * @returns array of backends
	 *
	 * Returns the names of all backends.
	 */
	public static function getBackends(){
		return self::$_backends;
	}

	/**
	 * @brief gets used backends
	 * @returns array of backends
	 *
	 * Returns the names of all used backends.
	 */
	public static function getUsedBackends(){
		return array_keys(self::$_usedBackends);
	}

	/**
	 * @brief Adds the backend to the list of used backends
	 * @param $backend default: database The backend to use for user managment
	 * @returns true/false
	 *
	 * Set the User Authentication Module
	 */
	public static function useBackend( $backend = 'database' ){
		// You'll never know what happens
		if( null === $backend OR !is_string( $backend )){
			$backend = 'database';
		}

		// Load backend
		switch( $backend ){
			case 'database':
			case 'mysql':
			case 'sqlite':
				self::$_usedBackends[$backend] = new OC_User_Database();
				break;
			default:
				$className = 'OC_USER_' . strToUpper($backend);
				self::$_usedBackends[$backend] = new $className();
				break;
		}

		true;
	}

	/**
	 * @brief Create a new user
	 * @param $uid The username of the user to create
	 * @param $password The password of the new user
	 * @returns true/false
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 *
	 * Allowed characters in the username are: "a-z", "A-Z", "0-9" and "_.@-"
	 */
	public static function createUser( $uid, $password ){
		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9" and "_.@-"
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $uid )){
			throw new Exception('Only the following characters are allowed in a username: "a-z", "A-Z", "0-9", and "_.@-"');
		}
		// No empty username
		if(trim($uid) == ''){
			throw new Exception('A valid username must be provided');
		}
		// No empty password
		if(trim($password) == ''){
			throw new Exception('A valid password must be provided');
		}

		// Check if user already exists
		if( self::userExists($uid) ){
			throw new Exception('The username is already being used');
		}


		$run = true;
		OC_Hook::emit( "OC_User", "pre_createUser", array( "run" => &$run, "uid" => $uid, "password" => $password ));

		if( $run ){
			//create the user in the first backend that supports creating users
			foreach(self::$_usedBackends as $backend){
				if(!$backend->implementsActions(OC_USER_BACKEND_CREATE_USER))
					continue;

				$backend->createUser($uid,$password);
				OC_Hook::emit( "OC_User", "post_createUser", array( "uid" => $uid, "password" => $password ));

				return true;
			}
		}
		return false;
	}

	/**
	 * @brief delete a user
	 * @param $uid The username of the user to delete
	 * @returns true/false
	 *
	 * Deletes a user
	 */
	public static function deleteUser( $uid ){
		$run = true;
		OC_Hook::emit( "OC_User", "pre_deleteUser", array( "run" => &$run, "uid" => $uid ));

		if( $run ){
			//delete the user from all backends
			foreach(self::$_usedBackends as $backend){
				if($backend->implementsActions(OC_USER_BACKEND_DELETE_USER)){
					$backend->deleteUser($uid);
				}
			}
			// We have to delete the user from all groups
			foreach( OC_Group::getUserGroups( $uid ) as $i ){
				OC_Group::removeFromGroup( $uid, $i );
			}
			// Delete the user's keys in preferences
			OC_Preferences::deleteUser($uid);
			// Emit and exit
			OC_Hook::emit( "OC_User", "post_deleteUser", array( "uid" => $uid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Try to login a user
	 * @param $uid The username of the user to log in
	 * @param $password The password of the user
	 * @returns true/false
	 *
	 * Log in a user and regenerate a new session - if the password is ok
	 */
	public static function login( $uid, $password ){
		$run = true;
		OC_Hook::emit( "OC_User", "pre_login", array( "run" => &$run, "uid" => $uid ));

		if( $run ){
			$uid=self::checkPassword( $uid, $password );
			if($uid){
				session_regenerate_id(true);
				self::setUserId($uid);
				OC_Hook::emit( "OC_User", "post_login", array( "uid" => $uid, 'password'=>$password ));
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief Sets user id for session and triggers emit
	 * @returns true
	 *
	 */
	public static function setUserId($uid) {
		$_SESSION['user_id'] = $uid;
		return true;
	}

	/**
	 * @brief Logs the current user out and kills all the session data
	 * @returns true
	 *
	 * Logout, destroys session
	 */
	public static function logout(){
		OC_Hook::emit( "OC_User", "logout", array());
		session_unset();
		session_destroy();
		OC_User::unsetMagicInCookie();
		return true;
	}

	/**
	 * @brief Check if the user is logged in
	 * @returns true/false
	 *
	 * Checks if the user is logged in
	 */
	public static function isLoggedIn(){
		static $is_login_checked = null;
		if (!is_null($is_login_checked)) {
			return $is_login_checked;
		}
		if( isset($_SESSION['user_id']) AND $_SESSION['user_id']) {
			OC_App::loadApps(array('authentication'));
			if (self::userExists($_SESSION['user_id']) ){
				return $is_login_checked = true;
			}
		}
		return $is_login_checked = false;
	}

	/**
	 * @brief get the user id of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getUser(){
		if( isset($_SESSION['user_id']) AND $_SESSION['user_id'] ){
			return $_SESSION['user_id'];
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Autogenerate a password
	 * @returns string
	 *
	 * generates a password
	 */
	public static function generatePassword(){
		return uniqId();
	}

	/**
	 * @brief Set password
	 * @param $uid The username
	 * @param $password The new password
	 * @returns true/false
	 *
	 * Change the password of a user
	 */
	public static function setPassword( $uid, $password ){
		$run = true;
		OC_Hook::emit( "OC_User", "pre_setPassword", array( "run" => &$run, "uid" => $uid, "password" => $password ));

		if( $run ){
			$success = false;
			foreach(self::$_usedBackends as $backend){
				if($backend->implementsActions(OC_USER_BACKEND_SET_PASSWORD)){
					if($backend->userExists($uid)){
						$success |= $backend->setPassword($uid,$password);
					}
				}
			}
			OC_Hook::emit( "OC_User", "post_setPassword", array( "uid" => $uid, "password" => $password ));
			return $success;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public static function checkPassword( $uid, $password ){
		foreach(self::$_usedBackends as $backend){
			if($backend->implementsActions(OC_USER_BACKEND_CHECK_PASSWORD)){
				$result=$backend->checkPassword( $uid, $password );
				if($result){
					return $result;
				}
			}
		}
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers(){
		$users=array();
		foreach(self::$_usedBackends as $backend){
			if($backend->implementsActions(OC_USER_BACKEND_GET_USERS)){
				$backendUsers=$backend->getUsers();
				if(is_array($backendUsers)){
					$users=array_merge($users,$backendUsers);
				}
			}
		}
		asort($users);
		return $users;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public static function userExists($uid){
		foreach(self::$_usedBackends as $backend){
			if($backend->implementsActions(OC_USER_BACKEND_USER_EXISTS)){
				$result=$backend->userExists($uid);
				if($result===true){
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @brief Set cookie value to use in next page load
	 * @param string $username username to be set
	 */
	public static function setMagicInCookie($username, $token){
		$secure_cookie = OC_Config::getValue("forcessl", false);
		setcookie("oc_username", $username, time()+60*60*24*15, '', '', $secure_cookie);
		setcookie("oc_token", $token, time()+60*60*24*15, '', '', $secure_cookie);
		setcookie("oc_remember_login", true, time()+60*60*24*15, '', '', $secure_cookie);
	}

	/**
	 * @brief Remove cookie for "remember username"
	 */
	public static function unsetMagicInCookie(){
		unset($_COOKIE["oc_username"]);
		unset($_COOKIE["oc_token"]);
		unset($_COOKIE["oc_remember_login"]);
		setcookie("oc_username", NULL, -1);
		setcookie("oc_token", NULL, -1);
		setcookie("oc_remember_login", NULL, -1);
	}
}
