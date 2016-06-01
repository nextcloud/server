<?php
/**
 * @author Aldo "xoen" Giambelluca <xoen@xoen.org>
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bartek Przybylski <bart.p.pl@gmail.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author shkdee <louis.traynard@m4x.org>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * This class provides wrapper methods for user management. Multiple backends are
 * supported. User management operations are delegated to the configured backend for
 * execution.
 *
 * Note that &run is deprecated and won't work anymore.
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

	/**
	 * @return \OC\User\Session
	 */
	public static function getUserSession() {
		return OC::$server->getUserSession();
	}

	private static $_usedBackends = array();

	private static $_setupedBackends = array();

	// bool, stores if a user want to access a resource anonymously, e.g if they open a public link
	private static $incognitoMode = false;

	/**
	 * Adds the backend to the list of used backends
	 *
	 * @param string|\OCP\UserInterface $backend default: database The backend to use for user management
	 * @return bool
	 *
	 * Set the User Authentication Module
	 */
	public static function useBackend($backend = 'database') {
		if ($backend instanceof \OCP\UserInterface) {
			self::$_usedBackends[get_class($backend)] = $backend;
			\OC::$server->getUserManager()->registerBackend($backend);
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
					\OCP\Util::writeLog('core', 'Adding user backend ' . $backend . '.', \OCP\Util::DEBUG);
					self::$_usedBackends[$backend] = new \OC\User\Database();
					\OC::$server->getUserManager()->registerBackend(self::$_usedBackends[$backend]);
					break;
				case 'dummy':
					self::$_usedBackends[$backend] = new \Test\Util\User\Dummy();
					\OC::$server->getUserManager()->registerBackend(self::$_usedBackends[$backend]);
					break;
				default:
					\OCP\Util::writeLog('core', 'Adding default user backend ' . $backend . '.', \OCP\Util::DEBUG);
					$className = 'OC_USER_' . strtoupper($backend);
					self::$_usedBackends[$backend] = new $className();
					\OC::$server->getUserManager()->registerBackend(self::$_usedBackends[$backend]);
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
		\OC::$server->getUserManager()->clearBackends();
	}

	/**
	 * setup the configured backends in config.php
	 */
	public static function setupBackends() {
		OC_App::loadApps(array('prelogin'));
		$backends = \OC::$server->getSystemConfig()->getValue('user_backends', array());
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
					\OCP\Util::writeLog('core', 'User backend ' . $class . ' already initialized.', \OCP\Util::DEBUG);
				}
			} else {
				\OCP\Util::writeLog('core', 'User backend ' . $class . ' not found.', \OCP\Util::ERROR);
			}
		}
	}

	/**

	 * Try to login a user using the magic cookie (remember login)
	 *
	 * @deprecated use \OCP\IUserSession::loginWithCookie()
	 * @param string $uid The username of the user to log in
	 * @param string $token
	 * @return bool
	 */
	public static function loginWithCookie($uid, $token) {
		return self::getUserSession()->loginWithCookie($uid, $token);
	}

	/**
	 * Try to login a user, assuming authentication
	 * has already happened (e.g. via Single Sign On).
	 *
	 * Log in a user and regenerate a new session.
	 *
	 * @param \OCP\Authentication\IApacheBackend $backend
	 * @return bool
	 */
	public static function loginWithApache(\OCP\Authentication\IApacheBackend $backend) {

		$uid = $backend->getCurrentUserId();
		$run = true;
		OC_Hook::emit("OC_User", "pre_login", array("run" => &$run, "uid" => $uid));

		if ($uid) {
			if (self::getUser() !== $uid) {
				self::setUserId($uid);
				self::setDisplayName($uid);
				self::getUserSession()->setLoginName($uid);
				$request = OC::$server->getRequest();
				self::getUserSession()->createSessionToken($request, $uid, $uid);
				// setup the filesystem
				OC_Util::setupFS($uid);
				// first call the post_login hooks, the login-process needs to be
				// completed before we can safely create the users folder.
				// For example encryption needs to initialize the users keys first
				// before we can create the user folder with the skeleton files
				OC_Hook::emit("OC_User", "post_login", array("uid" => $uid, 'password' => ''));
				//trigger creation of user home and /files folder
				\OC::$server->getUserFolder($uid);
			}
			return true;
		}
		return false;
	}

	/**
	 * Verify with Apache whether user is authenticated.
	 *
	 * @return boolean|null
	 *          true: authenticated
	 *          false: not authenticated
	 *          null: not handled / no backend available
	 */
	public static function handleApacheAuth() {
		$backend = self::findFirstActiveUsedBackend();
		if ($backend) {
			OC_App::loadApps();

			//setup extra user backends
			self::setupBackends();
			self::unsetMagicInCookie();

			return self::loginWithApache($backend);
		}

		return null;
	}


	/**
	 * Sets user id for session and triggers emit
	 *
	 * @param string $uid
	 */
	public static function setUserId($uid) {
		$userSession = \OC::$server->getUserSession();
		$userManager = \OC::$server->getUserManager();
		if ($user = $userManager->get($uid)) {
			$userSession->setUser($user);
		} else {
			\OC::$server->getSession()->set('user_id', $uid);
		}
	}

	/**
	 * Sets user display name for session
	 *
	 * @param string $uid
	 * @param string $displayName
	 * @return bool Whether the display name could get set
	 */
	public static function setDisplayName($uid, $displayName = null) {
		if (is_null($displayName)) {
			$displayName = $uid;
		}
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->setDisplayName($displayName);
		} else {
			return false;
		}
	}

	/**
	 * Check if the user is logged in, considers also the HTTP basic credentials
	 *
	 * @deprecated use \OC::$server->getUserSession()->isLoggedIn()
	 * @return bool
	 */
	public static function isLoggedIn() {
		return \OC::$server->getUserSession()->isLoggedIn();
	}

	/**
	 * set incognito mode, e.g. if a user wants to open a public link
	 *
	 * @param bool $status
	 */
	public static function setIncognitoMode($status) {
		self::$incognitoMode = $status;
	}

	/**
	 * get incognito mode status
	 *
	 * @return bool
	 */
	public static function isIncognitoMode() {
		return self::$incognitoMode;
	}

	/**
	 * Supplies an attribute to the logout hyperlink. The default behaviour
	 * is to return an href with '?logout=true' appended. However, it can
	 * supply any attribute(s) which are valid for <a>.
	 *
	 * @return string with one or more HTML attributes.
	 */
	public static function getLogoutAttribute() {
		$backend = self::findFirstActiveUsedBackend();
		if ($backend) {
			return $backend->getLogoutAttribute();
		}

		$logoutUrl = \OC::$server->getURLGenerator()->linkToRouteAbsolute(
			'core.login.logout',
			[
				'requesttoken' => \OCP\Util::callRegister(),
			]
		);

		return 'href="'.$logoutUrl.'"';
	}

	/**
	 * Check if the user is an admin user
	 *
	 * @param string $uid uid of the admin
	 * @return bool
	 */
	public static function isAdminUser($uid) {
		if (OC_Group::inGroup($uid, 'admin') && self::$incognitoMode === false) {
			return true;
		}
		return false;
	}


	/**
	 * get the user id of the user currently logged in.
	 *
	 * @return string|bool uid or false
	 */
	public static function getUser() {
		$uid = \OC::$server->getSession() ? \OC::$server->getSession()->get('user_id') : null;
		if (!is_null($uid) && self::$incognitoMode === false) {
			return $uid;
		} else {
			return false;
		}
	}

	/**
	 * get the display name of the user currently logged in.
	 *
	 * @param string $uid
	 * @return string uid or false
	 */
	public static function getDisplayName($uid = null) {
		if ($uid) {
			$user = \OC::$server->getUserManager()->get($uid);
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
	 * Autogenerate a password
	 *
	 * @return string
	 *
	 * generates a password
	 */
	public static function generatePassword() {
		return \OC::$server->getSecureRandom()->generate(30);
	}

	/**
	 * Set password
	 *
	 * @param string $uid The username
	 * @param string $password The new password
	 * @param string $recoveryPassword for the encryption app to reset encryption keys
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public static function setPassword($uid, $password, $recoveryPassword = null) {
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->setPassword($password, $recoveryPassword);
		} else {
			return false;
		}
	}

	/**
	 * Check whether user can change his avatar
	 *
	 * @param string $uid The username
	 * @return bool
	 *
	 * Check whether a specified user can change his avatar
	 */
	public static function canUserChangeAvatar($uid) {
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->canChangeAvatar();
		} else {
			return false;
		}
	}

	/**
	 * Check whether user can change his password
	 *
	 * @param string $uid The username
	 * @return bool
	 *
	 * Check whether a specified user can change his password
	 */
	public static function canUserChangePassword($uid) {
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->canChangePassword();
		} else {
			return false;
		}
	}

	/**
	 * Check whether user can change his display name
	 *
	 * @param string $uid The username
	 * @return bool
	 *
	 * Check whether a specified user can change his display name
	 */
	public static function canUserChangeDisplayName($uid) {
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->canChangeDisplayName();
		} else {
			return false;
		}
	}

	/**
	 * Check if the password is correct
	 *
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string|false user id a string on success, false otherwise
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public static function checkPassword($uid, $password) {
		$manager = \OC::$server->getUserManager();
		$username = $manager->checkPassword($uid, $password);
		if ($username !== false) {
			return $username->getUID();
		}
		return false;
	}

	/**
	 * @param string $uid The username
	 * @return string
	 *
	 * returns the path to the users home directory
	 * @deprecated Use \OC::$server->getUserManager->getHome()
	 */
	public static function getHome($uid) {
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->getHome();
		} else {
			return \OC::$server->getSystemConfig()->getValue('datadirectory', OC::$SERVERROOT . '/data') . '/' . $uid;
		}
	}

	/**
	 * Get a list of all users
	 *
	 * @return array an array of all uids
	 *
	 * Get a list of all users.
	 * @param string $search
	 * @param integer $limit
	 * @param integer $offset
	 */
	public static function getUsers($search = '', $limit = null, $offset = null) {
		$users = \OC::$server->getUserManager()->search($search, $limit, $offset);
		$uids = array();
		foreach ($users as $user) {
			$uids[] = $user->getUID();
		}
		return $uids;
	}

	/**
	 * Get a list of all users display name
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return array associative array with all display names (value) and corresponding uids (key)
	 *
	 * Get a list of all display names and user ids.
	 * @deprecated Use \OC::$server->getUserManager->searchDisplayName($search, $limit, $offset) instead.
	 */
	public static function getDisplayNames($search = '', $limit = null, $offset = null) {
		$displayNames = array();
		$users = \OC::$server->getUserManager()->searchDisplayName($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user->getUID()] = $user->getDisplayName();
		}
		return $displayNames;
	}

	/**
	 * check if a user exists
	 *
	 * @param string $uid the username
	 * @return boolean
	 */
	public static function userExists($uid) {
		return \OC::$server->getUserManager()->userExists($uid);
	}

	/**
	 * disables a user
	 *
	 * @param string $uid the user to disable
	 */
	public static function disableUser($uid) {
		$user = \OC::$server->getUserManager()->get($uid);
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
		$user = \OC::$server->getUserManager()->get($uid);
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
		$user = \OC::$server->getUserManager()->get($uid);
		if ($user) {
			return $user->isEnabled();
		} else {
			return false;
		}
	}

	/**
	 * Set cookie value to use in next page load
	 *
	 * @param string $username username to be set
	 * @param string $token
	 */
	public static function setMagicInCookie($username, $token) {
		self::getUserSession()->setMagicInCookie($username, $token);
	}

	/**
	 * Remove cookie for "remember username"
	 */
	public static function unsetMagicInCookie() {
		self::getUserSession()->unsetMagicInCookie();
	}

	/**
	 * Returns the first active backend from self::$_usedBackends.
	 *
	 * @return OCP\Authentication\IApacheBackend|null if no backend active, otherwise OCP\Authentication\IApacheBackend
	 */
	private static function findFirstActiveUsedBackend() {
		foreach (self::$_usedBackends as $backend) {
			if ($backend instanceof OCP\Authentication\IApacheBackend) {
				if ($backend->isSessionActive()) {
					return $backend;
				}
			}
		}

		return null;
	}
}
