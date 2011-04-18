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

if( !OC_CONFIG::getValue( "installed", false )){
	$_SESSION['user_id'] = '';
}

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
class OC_USER {
	// The backend used for user management
	private static $_backend = null;

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
	 * @brief Sets the backend
	 * @param $backend default: database The backend to use for user managment
	 * @returns true/false
	 *
	 * Set the User Authentication Module
	 */
	public static function setBackend( $backend = 'database' ){
		// You'll never know what happens
		if( null === $backend OR !is_string( $backend )){
			$backend = 'database';
		}

		// Load backend
		switch( $backend ){
			case 'database':
			case 'mysql':
			case 'sqlite':
				require_once('User/database.php');
				self::$_backend = new OC_USER_DATABASE();
				break;
			default:
				$className = 'OC_USER_' . strToUpper($backend);
				self::$_backend = new $className();
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
	 * Creates a new user. Basic checking of username is done in OC_USER
	 * itself, not in its subclasses.
	 *
	 * Allowed characters in the username are: "a-z", "A-Z", "0-9" and "_.@-"
	 */
	public static function createUser( $uid, $password ){
		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9" and "_.@-"
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $uid )){
			return false;
		}
		$run = true;
		OC_HOOK::emit( "OC_USER", "pre_createUser", array( "run" => &$run, "uid" => $uid, "password" => $password ));

		if( $run && self::$_backend->createUser( $uid, $password )){
			OC_HOOK::emit( "OC_USER", "post_createUser", array( "uid" => $uid, "password" => $password ));
			return true;
		}
		else{
			return false;
		}
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
		OC_HOOK::emit( "OC_USER", "pre_deleteUser", array( "run" => &$run, "uid" => $uid ));

		if( $run && self::$_backend->deleteUser( $uid )){
			OC_HOOK::emit( "OC_USER", "post_deleteUser", array( "uid" => $uid ));
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
	 * Log in a user - if the password is ok
	 */
	public static function login( $uid, $password ){
		$run = true;
		OC_HOOK::emit( "OC_USER", "pre_login", array( "run" => &$run, "uid" => $uid ));

		if( $run && self::$_backend->login( $uid, $password )){
			OC_HOOK::emit( "OC_USER", "post_login", array( "uid" => $uid ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Kick the user
	 * @returns true
	 *
	 * Logout, destroys session
	 */
	public static function logout(){
		OC_HOOK::emit( "OC_USER", "logout", array());
		return self::$_backend->logout();
	}

	/**
	 * @brief Check if the user is logged in
	 * @returns true/false
	 *
	 * Checks if the user is logged in
	 */
	public static function isLoggedIn(){
		return self::$_backend->isLoggedIn();
	}

	/**
	 * @brief Autogenerate a password
	 * @returns string
	 *
	 * generates a password
	 */
	public static function generatePassword(){
		return substr( md5( uniqId().time()), 0, 10 );
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
		OC_HOOK::emit( "OC_USER", "pre_setPassword", array( "run" => &$run, "uid" => $uid, "password" => $password ));

		if( $run && self::$_backend->setPassword( $uid, $password )){
			OC_HOOK::emit( "OC_USER", "post_setPassword", array( "uid" => $uid, "password" => $password ));
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param $uid The username
	 * @param $password The password
	 * @returns true/false
	 *
	 * Check if the password is correct without logging in the user
	 */
	public static function checkPassword( $uid, $password ){
		return self::$_backend->checkPassword( $uid, $password );
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers(){
		return self::$_backend->getUsers();
	}
}
