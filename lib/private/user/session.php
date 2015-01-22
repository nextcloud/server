<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\User;

use OC\Hooks\Emitter;
use OCP\IUserSession;

/**
 * Class Session
 *
 * Hooks available in scope \OC\User:
 * - preSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - postSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - preDelete(\OC\User\User $user)
 * - postDelete(\OC\User\User $user)
 * - preCreateUser(string $uid, string $password)
 * - postCreateUser(\OC\User\User $user)
 * - preLogin(string $user, string $password)
 * - postLogin(\OC\User\User $user, string $password)
 * - preRememberedLogin(string $uid)
 * - postRememberedLogin(\OC\User\User $user)
 * - logout()
 *
 * @package OC\User
 */
class Session implements IUserSession, Emitter {
	/**
	 * @var \OC\User\Manager $manager
	 */
	private $manager;

	/**
	 * @var \OC\Session\Session $session
	 */
	private $session;

	/**
	 * @var \OC\User\User $activeUser
	 */
	protected $activeUser;

	/**
	 * @param \OCP\IUserManager $manager
	 * @param \OCP\ISession $session
	 */
	public function __construct(\OCP\IUserManager $manager, \OCP\ISession $session) {
		$this->manager = $manager;
		$this->session = $session;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	public function listen($scope, $method, $callback) {
		$this->manager->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, $callback = null) {
		$this->manager->removeListener($scope, $method, $callback);
	}

	/**
	 * get the manager object
	 *
	 * @return \OC\User\Manager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * get the session object
	 *
	 * @return \OCP\ISession
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * set the session object
	 *
	 * @param \OCP\ISession $session
	 */
	public function setSession(\OCP\ISession $session) {
		if ($this->session instanceof \OCP\ISession) {
			$this->session->close();
		}
		$this->session = $session;
		$this->activeUser = null;
	}

	/**
	 * set the currently active user
	 *
	 * @param \OC\User\User|null $user
	 */
	public function setUser($user) {
		if (is_null($user)) {
			$this->session->remove('user_id');
		} else {
			$this->session->set('user_id', $user->getUID());
		}
		$this->activeUser = $user;
	}

	/**
	 * get the current active user
	 *
	 * @return \OCP\IUser|null Current user, otherwise null
	 */
	public function getUser() {
		// FIXME: This is a quick'n dirty work-around for the incognito mode as
		// described at https://github.com/owncloud/core/pull/12912#issuecomment-67391155
		if (\OC_User::isIncognitoMode()) {
			return null;
		}
		if ($this->activeUser) {
			return $this->activeUser;
		} else {
			$uid = $this->session->get('user_id');
			if ($uid !== null) {
				$this->activeUser = $this->manager->get($uid);
				return $this->activeUser;
			} else {
				return null;
			}
		}
	}

	/**
	 * Checks whether the user is logged in
	 *
	 * @return bool if logged in
	 */
	public function isLoggedIn() {
		return $this->getUser() !== null;
	}

	/**
	 * set the login name
	 *
	 * @param string|null $loginName for the logged in user
	 */
	public function setLoginName($loginName) {
		if (is_null($loginName)) {
			$this->session->remove('loginname');
		} else {
			$this->session->set('loginname', $loginName);
		}
	}

	/**
	 * get the login name of the current user
	 *
	 * @return string
	 */
	public function getLoginName() {
		if ($this->activeUser) {
			return $this->session->get('loginname');
		} else {
			$uid = $this->session->get('user_id');
			if ($uid) {
				$this->activeUser = $this->manager->get($uid);
				return $this->session->get('loginname');
			} else {
				return null;
			}
		}
	}

	/**
	 * try to login with the provided credentials
	 *
	 * @param string $uid
	 * @param string $password
	 * @return boolean|null
	 * @throws LoginException
	 */
	public function login($uid, $password) {
		$this->manager->emit('\OC\User', 'preLogin', array($uid, $password));
		$user = $this->manager->checkPassword($uid, $password);
		if ($user !== false) {
			if (!is_null($user)) {
				if ($user->isEnabled()) {
					$this->setUser($user);
					$this->setLoginName($uid);
					$this->manager->emit('\OC\User', 'postLogin', array($user, $password));
					if ($this->isLoggedIn()) {
						return true;
					} else {
						throw new LoginException('Login canceled by app');
					}
				} else {
					return false;
				}
			}
		} else {
			return false;
		}
	}

	/**
	 * perform login using the magic cookie (remember login)
	 *
	 * @param string $uid the username
	 * @param string $currentToken
	 * @return bool
	 */
	public function loginWithCookie($uid, $currentToken) {
		$this->manager->emit('\OC\User', 'preRememberedLogin', array($uid));
		$user = $this->manager->get($uid);
		if (is_null($user)) {
			// user does not exist
			return false;
		}

		// get stored tokens
		$tokens = \OC::$server->getConfig()->getUserKeys($uid, 'login_token');
		// test cookies token against stored tokens
		if (!in_array($currentToken, $tokens, true)) {
			return false;
		}
		// replace successfully used token with a new one
		\OC::$server->getConfig()->deleteUserValue($uid, 'login_token', $currentToken);
		$newToken = \OC::$server->getSecureRandom()->getMediumStrengthGenerator()->generate(32);
		\OC::$server->getConfig()->setUserValue($uid, 'login_token', $newToken, time());
		$this->setMagicInCookie($user->getUID(), $newToken);

		//login
		$this->setUser($user);
		$this->manager->emit('\OC\User', 'postRememberedLogin', array($user));
		return true;
	}

	/**
	 * logout the user from the session
	 */
	public function logout() {
		$this->manager->emit('\OC\User', 'logout');
		$this->setUser(null);
		$this->setLoginName(null);
		$this->unsetMagicInCookie();
		$this->session->clear();
	}

	/**
	 * Set cookie value to use in next page load
	 *
	 * @param string $username username to be set
	 * @param string $token
	 */
	public function setMagicInCookie($username, $token) {
		$secure_cookie = \OC_Config::getValue("forcessl", false); //TODO: DI for cookies and OC_Config
		$expires = time() + \OC_Config::getValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		setcookie("oc_username", $username, $expires, \OC::$WEBROOT, '', $secure_cookie);
		setcookie("oc_token", $token, $expires, \OC::$WEBROOT, '', $secure_cookie, true);
		setcookie("oc_remember_login", "1", $expires, \OC::$WEBROOT, '', $secure_cookie);
	}

	/**
	 * Remove cookie for "remember username"
	 */
	public function unsetMagicInCookie() {
		unset($_COOKIE["oc_username"]); //TODO: DI
		unset($_COOKIE["oc_token"]);
		unset($_COOKIE["oc_remember_login"]);
		setcookie('oc_username', '', time() - 3600, \OC::$WEBROOT);
		setcookie('oc_token', '', time() - 3600, \OC::$WEBROOT);
		setcookie('oc_remember_login', '', time() - 3600, \OC::$WEBROOT);
		// old cookies might be stored under /webroot/ instead of /webroot
		// and Firefox doesn't like it!
		setcookie('oc_username', '', time() - 3600, \OC::$WEBROOT . '/');
		setcookie('oc_token', '', time() - 3600, \OC::$WEBROOT . '/');
		setcookie('oc_remember_login', '', time() - 3600, \OC::$WEBROOT . '/');
	}
}
