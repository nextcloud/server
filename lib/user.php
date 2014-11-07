<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 * This class provides wrapper methods for user management. Multiple backends are
 * supported. User management operations are delegated to the configured backend for
 * execution.
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

	private static $_setupedBackends = array();

	// Backends available (except database)
	private static $_backends = array();

	/**
	 * @brief registers backend
	 * @param $name name of the backend
	 * @returns true/false
	 *
	 * Makes a list of backends that can be used by other modules
	 */
	public static function registerBackend( $backend ) {
		self::$_backends[] = $backend;
		return true;
	}

	/**
	 * @brief gets available backends
	 * @returns array of backends
	 *
	 * Returns the names of all backends.
	 */
	public static function getBackends() {
		return self::$_backends;
	}

	/**
	 * @brief gets used backends
	 * @returns array of backends
	 *
	 * Returns the names of all used backends.
	 */
	public static function getUsedBackends() {
		return array_keys(self::$_usedBackends);
	}

	/**
	 * @brief Adds the backend to the list of used backends
	 * @param $backend default: database The backend to use for user managment
	 * @returns true/false
	 *
	 * Set the User Authentication Module
	 */
	public static function useBackend( $backend = 'database' ) {
		if($backend instanceof OC_User_Interface) {
			self::$_usedBackends[get_class($backend)]=$backend;
		} else {
			// You'll never know what happens
			if( null === $backend OR !is_string( $backend )) {
				$backend = 'database';
			}

			// Load backend
			switch( $backend ) {
				case 'database':
				case 'mysql':
				case 'sqlite':
					OC_Log::write('core', 'Adding user backend '.$backend.'.', OC_Log::DEBUG);
					self::$_usedBackends[$backend] = new OC_User_Database();
					break;
				default:
					OC_Log::write('core', 'Adding default user backend '.$backend.'.', OC_Log::DEBUG);
					$className = 'OC_USER_' . strToUpper($backend);
					self::$_usedBackends[$backend] = new $className();
					break;
			}
		}
		return true;
	}

	/**
	 * remove all used backends
	 */
	public static function clearBackends() {
		self::$_usedBackends=array();
	}

	/**
	 * setup the configured backends in config.php
	 */
	public static function setupBackends() {
		$backends=OC_Config::getValue('user_backends', array());
		foreach($backends as $i=>$config) {
			$class=$config['class'];
			$arguments=$config['arguments'];
			if(class_exists($class)) {
				if(array_search($i, self::$_setupedBackends)===false) {
					// make a reflection object
					$reflectionObj = new ReflectionClass($class);

					// use Reflection to create a new instance, using the $args
					$backend = $reflectionObj->newInstanceArgs($arguments);
					self::useBackend($backend);
					self::$_setupedBackends[] = $i;
				} else {
					OC_Log::write('core', 'User backend '.$class.' already initialized.', OC_Log::DEBUG);
				}
			} else {
				OC_Log::write('core', 'User backend '.$class.' not found.', OC_Log::ERROR);
			}
		}
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
	public static function createUser( $uid, $password ) {
		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9" and "_.@-"
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $uid )) {
			throw new Exception('Only the following characters are allowed in a username:'
				.' "a-z", "A-Z", "0-9", and "_.@-"');
		}
		// No empty username
		if(trim($uid) == '') {
			throw new Exception('A valid username must be provided');
		}
		// No empty password
		if(trim($password) == '') {
			throw new Exception('A valid password must be provided');
		}

		// Check if user already exists
		if( self::userExistsForCreation($uid) ) {
			throw new Exception('The username is already being used');
		}


		$run = true;
		OC_Hook::emit( "OC_User", "pre_createUser", array( "run" => &$run, "uid" => $uid, "password" => $password ));

		if( $run ) {
			//create the user in the first backend that supports creating users
			foreach(self::$_usedBackends as $backend) {
				if(!$backend->implementsActions(OC_USER_BACKEND_CREATE_USER))
					continue;

				$backend->createUser($uid, $password);
				OC_Hook::emit( "OC_User", "post_createUser", array( "uid" => $uid, "password" => $password ));

				return self::userExists($uid);
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
	public static function deleteUser( $uid ) {
		$run = true;
		OC_Hook::emit( "OC_User", "pre_deleteUser", array( "run" => &$run, "uid" => $uid ));

		if( $run ) {
			//delete the user from all backends
			foreach(self::$_usedBackends as $backend) {
				$backend->deleteUser($uid);
			}
			if (self::userExists($uid)) {
				return false;
			}
			// We have to delete the user from all groups
			foreach( OC_Group::getUserGroups( $uid ) as $i ) {
				OC_Group::removeFromGroup( $uid, $i );
			}
			// Delete the user's keys in preferences
			OC_Preferences::deleteUser($uid);

			$home = self::getHome($uid);

			\OC\Files\Cache\Cache::removeStorage('local::' . $home . '/');
			\OC\Files\Cache\Cache::removeStorage('home::' . $uid);

			// Delete user files in /data/
			OC_Helper::rmdirr(OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" ) . '/'.$uid.'/');

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
	public static function login( $uid, $password ) {
		$uid = str_replace("\0", '', $uid);
		$password = str_replace("\0", '', $password);

		$run = true;
		OC_Hook::emit( "OC_User", "pre_login", array( "run" => &$run, "uid" => $uid ));

		if( $run ) {
			$uid = self::checkPassword( $uid, $password );
			$enabled = self::isEnabled($uid);
			if($uid && $enabled) {
				session_regenerate_id(true);
				self::setUserId($uid);
				self::setDisplayName($uid);
				OC_Hook::emit( "OC_User", "post_login", array( "uid" => $uid, 'password'=>$password ));
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief Sets user id for session and triggers emit
	 */
	public static function setUserId($uid) {
		$_SESSION['user_id'] = $uid;
	}

	/**
	 * @brief Sets user display name for session
	 */
	public static function setDisplayName($uid, $displayName = null) {
		$result = false;
		if ($displayName ) {
			foreach(self::$_usedBackends as $backend) {
				if($backend->implementsActions(OC_USER_BACKEND_SET_DISPLAYNAME)) {
					if($backend->userExists($uid)) {
						$result |= $backend->setDisplayName($uid, $displayName);
					}
				}
			}
		} else {
			$displayName = self::determineDisplayName($uid);
			$result = true;
		}
		if (OC_User::getUser() === $uid) {
			$_SESSION['display_name'] = $displayName;
		}
		return $result;
	}


	/**
	 * @brief get display name
	 * @param $uid The username
	 * @returns string display name or uid if no display name is defined
	 *
	 */
	private static function determineDisplayName( $uid ) {
		foreach(self::$_usedBackends as $backend) {
			if($backend->implementsActions(OC_USER_BACKEND_GET_DISPLAYNAME)) {
				$result=$backend->getDisplayName( $uid );
				if($result) {
					return $result;
				}
			}
		}
		return $uid;
	}

	/**
	 * @brief Logs the current user out and kills all the session data
	 *
	 * Logout, destroys session
	 */
	public static function logout() {
		OC_Hook::emit( "OC_User", "logout", array());
		session_unset();
		session_destroy();
		OC_User::unsetMagicInCookie();
	}

	/**
	 * @brief Check if the user is logged in
	 * @returns true/false
	 *
	 * Checks if the user is logged in
	 */
	public static function isLoggedIn() {
		if( isset($_SESSION['user_id']) AND $_SESSION['user_id']) {
			OC_App::loadApps(array('authentication'));
			self::setupBackends();
			if (self::userExists($_SESSION['user_id']) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @brief Check if the user is an admin user
	 * @param $uid uid of the admin
	 * @returns bool
	 */
	public static function isAdminUser($uid) {
		if(OC_Group::inGroup($uid, 'admin' )) {
			return true;
		}
		return false;
	}


	/**
	 * @brief get the user id of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getUser() {
		if( isset($_SESSION['user_id']) AND $_SESSION['user_id'] ) {
			return $_SESSION['user_id'];
		}
		else{
			return false;
		}
	}

	/**
	 * @brief get the display name of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getDisplayName($user=null) {
		if ( $user ) {
			return self::determineDisplayName($user);
		} else if( isset($_SESSION['display_name']) AND $_SESSION['display_name'] ) {
			return $_SESSION['display_name'];
		} else if ( $user = self::getUser() ) {
			return self::determineDisplayName($user);
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
	public static function generatePassword() {
		return uniqId();
	}

	/**
	 * @brief Set password
	 * @param $uid The username
	 * @param $password The new password
	 * @param $recoveryPassword for the encryption app to reset encryption keys
	 * @returns true/false
	 *
	 * Change the password of a user
	 */
	public static function setPassword( $uid, $password, $recoveryPassword = null ) {
		$run = true;
		OC_Hook::emit( "OC_User", "pre_setPassword", array( "run" => &$run, "uid" => $uid, "password" => $password, "recoveryPassword" => $recoveryPassword ));

		if( $run ) {
			$success = false;
			foreach(self::$_usedBackends as $backend) {
				if($backend->implementsActions(OC_USER_BACKEND_SET_PASSWORD)) {
					if($backend->userExists($uid)) {
						$success |= $backend->setPassword($uid, $password);
					}
				}
			}
			// invalidate all login cookies
			OC_Preferences::deleteApp($uid, 'login_token');
			OC_Hook::emit( "OC_User", "post_setPassword", array( "uid" => $uid, "password" => $password, "recoveryPassword" => $recoveryPassword ));
			return $success;
		}
		else{
			return false;
		}
	}

	/**
	 * @brief Check whether user can change his password
	 * @param $uid The username
	 * @returns true/false
	 *
	 * Check whether a specified user can change his password
	 */
	public static function canUserChangePassword($uid) {
		foreach(self::$_usedBackends as $backend) {
			if($backend->implementsActions(OC_USER_BACKEND_SET_PASSWORD)) {
				if($backend->userExists($uid)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @brief Check whether user can change his display name
	 * @param $uid The username
	 * @returns true/false
	 *
	 * Check whether a specified user can change his display name
	 */
	public static function canUserChangeDisplayName($uid) {
		if (OC_Config::getValue('allow_user_to_change_display_name', true)) {
			foreach(self::$_usedBackends as $backend) {
				if($backend->implementsActions(OC_USER_BACKEND_SET_DISPLAYNAME)) {
					if($backend->userExists($uid)) {
						return true;
					}
				}
			}
		}
		return false;
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
	public static function checkPassword( $uid, $password ) {
		foreach(self::$_usedBackends as $backend) {
			if($backend->implementsActions(OC_USER_BACKEND_CHECK_PASSWORD)) {
				$result=$backend->checkPassword( $uid, $password );
				if($result) {
					return $result;
				}
			}
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @returns string
	 *
	 * returns the path to the users home directory
	 */
	public static function getHome($uid) {
		foreach(self::$_usedBackends as $backend) {
			if($backend->implementsActions(OC_USER_BACKEND_GET_HOME) && $backend->userExists($uid)) {
				$result=$backend->getHome($uid);
				if($result) {
					return $result;
				}
			}
		}
		return OC_Config::getValue( "datadirectory", OC::$SERVERROOT."/data" ) . '/' . $uid;
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers($search = '', $limit = null, $offset = null) {
		$users = array();
		foreach (self::$_usedBackends as $backend) {
			$backendUsers = $backend->getUsers($search, $limit, $offset);
			if (is_array($backendUsers)) {
				$users = array_merge($users, $backendUsers);
			}
		}
		asort($users);
		return $users;
	}

	/**
	 * @brief Get a list of all users display name
	 * @returns associative array with all display names (value) and corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public static function getDisplayNames($search = '', $limit = null, $offset = null) {
		$displayNames = array();
		foreach (self::$_usedBackends as $backend) {
			$backendDisplayNames = $backend->getDisplayNames($search, $limit, $offset);
			if (is_array($backendDisplayNames)) {
				$displayNames = $displayNames + $backendDisplayNames;
			}
		}
		asort($displayNames);
		return $displayNames;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @param string $excludingBackend (default none)
	 * @return boolean
	 */
	public static function userExists($uid, $excludingBackend=null) {
		foreach(self::$_usedBackends as $backend) {
			if (!is_null($excludingBackend) && !strcmp(get_class($backend), $excludingBackend)) {
				OC_Log::write('OC_User', $excludingBackend . 'excluded from user existance check.', OC_Log::DEBUG);
				continue;
			}
			$result=$backend->userExists($uid);
			if($result===true) {
				return true;
			}
		}
		return false;
	}

    public static function userExistsForCreation($uid) {
        foreach(self::$_usedBackends as $backend) {
            if(!$backend->hasUserListings())
                continue;

            $result=$backend->userExists($uid);
            if($result===true) {
                return true;
            }
        }
        return false;
    }

	/**
	 * disables a user
	 * @param string $userid the user to disable
	 */
	public static function disableUser($userid) {
		$sql = "INSERT INTO `*PREFIX*preferences` (`userid`, `appid`, `configkey`, `configvalue`) VALUES(?, ?, ?, ?)";
		$stmt = OC_DB::prepare($sql);
		if ( ! OC_DB::isError($stmt) ) {
			$result = $stmt->execute(array($userid, 'core', 'enabled', 'false'));
			if ( OC_DB::isError($result) ) {
				OC_Log::write('OC_User', 'could not enable user: '. OC_DB::getErrorMessage($result), OC_Log::ERROR);
			}
		} else {
			OC_Log::write('OC_User', 'could not disable user: '. OC_DB::getErrorMessage($stmt), OC_Log::ERROR);
		}
	}

	/**
	 * enable a user
	 * @param string $userid
	 */
	public static function enableUser($userid) {
		$sql = 'DELETE FROM `*PREFIX*preferences`'
			." WHERE `userid` = ? AND `appid` = ? AND `configkey` = ? AND `configvalue` = ?";
		$stmt = OC_DB::prepare($sql);
		if ( ! OC_DB::isError($stmt) ) {
			$result = $stmt->execute(array($userid, 'core', 'enabled', 'false'));
			if ( OC_DB::isError($result) ) {
				OC_Log::write('OC_User', 'could not enable user: '. OC_DB::getErrorMessage($result), OC_Log::ERROR);
			}
		} else {
			OC_Log::write('OC_User', 'could not enable user: '. OC_DB::getErrorMessage($stmt), OC_Log::ERROR);
		}
	}

	/**
	 * checks if a user is enabled
	 * @param string $userid
	 * @return bool
	 */
	public static function isEnabled($userid) {
		$sql = 'SELECT `userid` FROM `*PREFIX*preferences`'
			.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ? AND `configvalue` = ?';
		if (OC_Config::getValue( 'dbtype', 'sqlite' ) === 'oci') { //FIXME oracle hack
			$sql = 'SELECT `userid` FROM `*PREFIX*preferences`'
				.' WHERE `userid` = ? AND `appid` = ? AND `configkey` = ? AND to_char(`configvalue`) = ?';
		}
		$stmt = OC_DB::prepare($sql);
		if ( ! OC_DB::isError($stmt) ) {
			$result = $stmt->execute(array($userid, 'core', 'enabled', 'false'));
			if ( ! OC_DB::isError($result) ) {
				return $result->numRows() ? false : true;
			} else {
				OC_Log::write('OC_User',
					'could not check if enabled: '. OC_DB::getErrorMessage($result),
					OC_Log::ERROR);
			}
		} else {
			OC_Log::write('OC_User', 'could not check if enabled: '. OC_DB::getErrorMessage($stmt), OC_Log::ERROR);
		}
		return false;
	}

	/**
	 * @brief Set cookie value to use in next page load
	 * @param string $username username to be set
	 */
	public static function setMagicInCookie($username, $token) {
		$secure_cookie = OC_Config::getValue("forcessl", false);
		$expires = time() + OC_Config::getValue('remember_login_cookie_lifetime', 60*60*24*15);
		setcookie("oc_username", $username, $expires, \OC::$WEBROOT, '', $secure_cookie);
		setcookie("oc_token", $token, $expires, \OC::$WEBROOT, '', $secure_cookie, true);
		setcookie("oc_remember_login", true, $expires, \OC::$WEBROOT, '', $secure_cookie);
	}

	/**
	 * @brief Remove cookie for "remember username"
	 */
	public static function unsetMagicInCookie() {
		unset($_COOKIE["oc_username"]); //TODO: DI
		unset($_COOKIE["oc_token"]);
		unset($_COOKIE["oc_remember_login"]);
		setcookie('oc_username', '', time()-3600, \OC::$WEBROOT);
		setcookie('oc_token', '', time()-3600, \OC::$WEBROOT);
		setcookie('oc_remember_login', '', time()-3600, \OC::$WEBROOT);
		// old cookies might be stored under /webroot/ instead of /webroot
		// and Firefox doesn't like it!
		setcookie('oc_username', '', time()-3600, \OC::$WEBROOT . '/');
		setcookie('oc_token', '', time()-3600, \OC::$WEBROOT . '/');
		setcookie('oc_remember_login', '', time()-3600, \OC::$WEBROOT . '/');
	}
}
