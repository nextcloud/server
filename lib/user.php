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
 *   pre_setPassword(&run, uid, password, recoveryPassword)
 *   post_setPassword(uid, password, recoveryPassword)
 *   pre_login(&run, uid, password)
 *   post_login(uid)
 *   logout()
 */
class OC_User {
	public static $userSession = null;

	public static function getUserSession() {
		if (!self::$userSession) {
			$manager = new \OC\User\Manager();
			self::$userSession = new \OC\User\Session($manager, \OC::$session);
			self::$userSession->listen('\OC\User', 'preCreateUser', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_createUser', array('run' => true, 'uid' => $uid, 'password' => $password));
			});
			self::$userSession->listen('\OC\User', 'postCreateUser', function ($user, $password) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_createUser', array('uid' => $user->getUID(), 'password' => $password));
			});
			self::$userSession->listen('\OC\User', 'preDelete', function ($user) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'pre_deleteUser', array('run' => true, 'uid' => $user->getUID()));
			});
			self::$userSession->listen('\OC\User', 'postDelete', function ($user) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_deleteUser', array('uid' => $user->getUID()));
			});
			self::$userSession->listen('\OC\User', 'preSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var $user \OC\User\User */
				OC_Hook::emit('OC_User', 'pre_setPassword', array('run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword));
			});
			self::$userSession->listen('\OC\User', 'postSetPassword', function ($user, $password, $recoveryPassword) {
				/** @var $user \OC\User\User */
				OC_Hook::emit('OC_User', 'post_setPassword', array('run' => true, 'uid' => $user->getUID(), 'password' => $password, 'recoveryPassword' => $recoveryPassword));
			});
			self::$userSession->listen('\OC\User', 'preLogin', function ($uid, $password) {
				\OC_Hook::emit('OC_User', 'pre_login', array('run' => true, 'uid' => $uid, 'password' => $password));
			});
			self::$userSession->listen('\OC\User', 'postLogin', function ($user, $password) {
				/** @var $user \OC\User\User */
				\OC_Hook::emit('OC_User', 'post_login', array('run' => true, 'uid' => $user->getUID(), 'password' => $password));
			});
			self::$userSession->listen('\OC\User', 'logout', function () {
				\OC_Hook::emit('OC_User', 'logout', array());
			});
		}
		return self::$userSession;
	}

	/**
	 * @return \OC\User\Manager
	 */
	public static function getManager() {
		return self::getUserSession()->getManager();
	}

	private static $_backends = array();

	private static $_usedBackends = array();

	private static $_setupedBackends = array();

	/**
	 * @brief registers backend
	 * @param string $backend name of the backend
	 * @deprecated Add classes by calling useBackend with a class instance instead
	 * @return bool
	 *
	 * Makes a list of backends that can be used by other modules
	 */
	public static function registerBackend($backend) {
		self::$_backends[] = $backend;
		return true;
	}

	/**
	 * @brief gets available backends
	 * @deprecated
	 * @returns array of backends
	 *
	 * Returns the names of all backends.
	 */
	public static function getBackends() {
		return self::$_backends;
	}

	/**
	 * @brief gets used backends
	 * @deprecated
	 * @returns array of backends
	 *
	 * Returns the names of all used backends.
	 */
	public static function getUsedBackends() {
		return array_keys(self::$_usedBackends);
	}

	/**
	 * @brief Adds the backend to the list of used backends
	 * @param string | OC_User_Backend $backend default: database The backend to use for user management
	 * @return bool
	 *
	 * Set the User Authentication Module
	 */
	public static function useBackend($backend = 'database') {
		if ($backend instanceof OC_User_Interface) {
			OC_Log::write('core', 'Adding user backend instance of ' . get_class($backend) . '.', OC_Log::DEBUG);
			self::$_usedBackends[get_class($backend)] = $backend;
			self::getManager()->registerBackend($backend);
		} else {
			// You'll never know what happens
			if (null === $backend OR !is_string($backend)) {
				$backend = 'database';
			}

			// Load backend
			switch ($backend) {
				case 'database':
				case 'mysql':
				case 'sqlite':
					OC_Log::write('core', 'Adding user backend ' . $backend . '.', OC_Log::DEBUG);
					self::$_usedBackends[$backend] = new OC_User_Database();
					self::getManager()->registerBackend(self::$_usedBackends[$backend]);
					break;
				default:
					OC_Log::write('core', 'Adding default user backend ' . $backend . '.', OC_Log::DEBUG);
					$className = 'OC_USER_' . strToUpper($backend);
					self::$_usedBackends[$backend] = new $className();
					self::getManager()->registerBackend(self::$_usedBackends[$backend]);
					break;
			}
		}
		return true;
	}

	/**
	 * remove all used backends
	 */
	public static function clearBackends() {
		self::$_usedBackends = array();
		self::getManager()->clearBackends();
	}

	/**
	 * setup the configured backends in config.php
	 */
	public static function setupBackends() {
		$backends = OC_Config::getValue('user_backends', array());
		foreach ($backends as $i => $config) {
			$class = $config['class'];
			$arguments = $config['arguments'];
			if (class_exists($class)) {
				if (array_search($i, self::$_setupedBackends) === false) {
					// make a reflection object
					$reflectionObj = new ReflectionClass($class);

					// use Reflection to create a new instance, using the $args
					$backend = $reflectionObj->newInstanceArgs($arguments);
					self::useBackend($backend);
					self::$_setupedBackends[] = $i;
				} else {
					OC_Log::write('core', 'User backend ' . $class . ' already initialized.', OC_Log::DEBUG);
				}
			} else {
				OC_Log::write('core', 'User backend ' . $class . ' not found.', OC_Log::ERROR);
			}
		}
	}

	/**
	 * @brief Create a new user
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @throws Exception
	 * @return bool true/false
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 *
	 * Allowed characters in the username are: "a-z", "A-Z", "0-9" and "_.@-"
	 */
	public static function createUser($uid, $password) {
		return self::getManager()->createUser($uid, $password);
	}

	/**
	 * @brief delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public static function deleteUser($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			$user->delete();

			// We have to delete the user from all groups
			foreach (OC_Group::getUserGroups($uid) as $i) {
				OC_Group::removeFromGroup($uid, $i);
			}
			// Delete the user's keys in preferences
			OC_Preferences::deleteUser($uid);

			// Delete user files in /data/
			OC_Helper::rmdirr(OC_Config::getValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $uid . '/');
		}
	}

	/**
	 * @brief Try to login a user
	 * @param $uid The username of the user to log in
	 * @param $password The password of the user
	 * @return bool
	 *
	 * Log in a user and regenerate a new session - if the password is ok
	 */
	public static function login($uid, $password) {
		return self::getUserSession()->login($uid, $password);
	}

	/**
	 * @brief Sets user id for session and triggers emit
	 */
	public static function setUserId($uid) {
		OC::$session->set('user_id', $uid);
	}

	/**
	 * @brief Sets user display name for session
	 */
	public static function setDisplayName($uid, $displayName = null) {
		if (is_null($displayName)) {
			$displayName = $uid;
		}
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->setDisplayName($displayName);
		} else {
			return false;
		}
	}

	/**
	 * @brief Logs the current user out and kills all the session data
	 *
	 * Logout, destroys session
	 */
	public static function logout() {
		self::getUserSession()->logout();
	}

	/**
	 * @brief Check if the user is logged in
	 * @returns bool
	 *
	 * Checks if the user is logged in
	 */
	public static function isLoggedIn() {
		if (\OC::$session->get('user_id')) {
			OC_App::loadApps(array('authentication'));
			self::setupBackends();
			return self::userExists(\OC::$session->get('user_id'));
		}
		return false;
	}

	/**
	 * @brief Check if the user is an admin user
	 * @param string $uid uid of the admin
	 * @return bool
	 */
	public static function isAdminUser($uid) {
		if (OC_Group::inGroup($uid, 'admin')) {
			return true;
		}
		return false;
	}


	/**
	 * @brief get the user id of the user currently logged in.
	 * @return string uid or false
	 */
	public static function getUser() {
		$uid = OC::$session ? OC::$session->get('user_id') : null;
		if (!is_null($uid)) {
			return $uid;
		} else {
			return false;
		}
	}

	/**
	 * @brief get the display name of the user currently logged in.
	 * @param string $uid
	 * @return string uid or false
	 */
	public static function getDisplayName($uid = null) {
		if ($uid) {
			$user = self::getManager()->get($uid);
			if ($user) {
				return $user->getDisplayName();
			} else {
				return $uid;
			}
		} else {
			$user = self::getUserSession()->getUser();
			if ($user) {
				return $user->getDisplayName();
			} else {
				return false;
			}
		}
	}

	/**
	 * @brief Autogenerate a password
	 * @return string
	 *
	 * generates a password
	 */
	public static function generatePassword() {
		return OC_Util::generate_random_bytes(30);
	}

	/**
	 * @brief Set password
	 * @param string $uid The username
	 * @param string $password The new password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public static function setPassword($uid, $password, $recoveryPassword = null) {
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->setPassword($password, $recoveryPassword);
		} else {
			return false;
		}
	}

	/**
	 * @brief Check whether user can change his password
	 * @param string $uid The username
	 * @return bool
	 *
	 * Check whether a specified user can change his password
	 */
	public static function canUserChangePassword($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->canChangePassword();
		} else {
			return false;
		}
	}

	/**
	 * @brief Check whether user can change his display name
	 * @param string $uid The username
	 * @return bool
	 *
	 * Check whether a specified user can change his display name
	 */
	public static function canUserChangeDisplayName($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->canChangeDisplayName();
		} else {
			return false;
		}
	}

	/**
	 * @brief Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return bool
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public static function checkPassword($uid, $password) {
		$user = self::getManager()->get($uid);
		if ($user) {
			if ($user->checkPassword($password)) {
				return $user->getUID();
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param string $uid The username
	 * @return string
	 *
	 * returns the path to the users home directory
	 */
	public static function getHome($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->getHome();
		} else {
			return OC_Config::getValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $uid;
		}
	}

	/**
	 * @brief Get a list of all users
	 * @returns array with all uids
	 *
	 * Get a list of all users.
	 */
	public static function getUsers($search = '', $limit = null, $offset = null) {
		$users = self::getManager()->search($search, $limit, $offset);
		$uids = array();
		foreach ($users as $user) {
			$uids[] = $user->getUID();
		}
		return $uids;
	}

	/**
	 * @brief Get a list of all users display name
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array associative array with all display names (value) and corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 */
	public static function getDisplayNames($search = '', $limit = null, $offset = null) {
		$displayNames = array();
		$users = self::getManager()->searchDisplayName($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user->getUID()] = $user->getDisplayName();
		}
		return $displayNames;
	}

	/**
	 * @brief check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public static function userExists($uid) {
		return self::getManager()->userExists($uid);
	}

	/**
	 * disables a user
	 *
	 * @param string $uid the user to disable
	 */
	public static function disableUser($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			$user->setEnabled(false);
		}
	}

	/**
	 * enable a user
	 *
	 * @param string $uid
	 */
	public static function enableUser($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			$user->setEnabled(true);
		}
	}

	/**
	 * checks if a user is enabled
	 *
	 * @param string $uid
	 * @return bool
	 */
	public static function isEnabled($uid) {
		$user = self::getManager()->get($uid);
		if ($user) {
			return $user->isEnabled();
		} else {
			return false;
		}
	}

	/**
	 * @brief Set cookie value to use in next page load
	 * @param string $username username to be set
	 * @param string $token
	 */
	public static function setMagicInCookie($username, $token) {
		self::getUserSession()->setMagicInCookie($username, $token);
	}

	/**
	 * @brief Remove cookie for "remember username"
	 */
	public static function unsetMagicInCookie() {
		self::getUserSession()->unsetMagicInCookie();
	}
}
