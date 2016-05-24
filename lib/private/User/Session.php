<?php

/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OC\User;

use OC;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Hooks\Emitter;
use OC_User;
use OC_Util;
use OCA\DAV\Connector\Sabre\Auth;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;

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
	
	/** @var Manager $manager */
	private $manager;

	/** @var ISession $session */
	private $session;

	/** @var ITimeFactory */
	private $timeFacory;

	/** @var IProvider */
	private $tokenProvider;

	/** @var User $activeUser */
	protected $activeUser;

	/**
	 * @param IUserManager $manager
	 * @param ISession $session
	 * @param ITimeFactory $timeFacory
	 * @param IProvider $tokenProvider
	 */
	public function __construct(IUserManager $manager, ISession $session, ITimeFactory $timeFacory, $tokenProvider) {
		$this->manager = $manager;
		$this->session = $session;
		$this->timeFacory = $timeFacory;
		$this->tokenProvider = $tokenProvider;
	}

	/**
	 * @param IProvider $provider
	 */
	public function setTokenProvider(IProvider $provider) {
		$this->tokenProvider = $provider;
	}

	/**
	 * @param string $scope
	 * @param string $method
	 * @param callable $callback
	 */
	public function listen($scope, $method, callable $callback) {
		$this->manager->listen($scope, $method, $callback);
	}

	/**
	 * @param string $scope optional
	 * @param string $method optional
	 * @param callable $callback optional
	 */
	public function removeListener($scope = null, $method = null, callable $callback = null) {
		$this->manager->removeListener($scope, $method, $callback);
	}

	/**
	 * get the manager object
	 *
	 * @return Manager
	 */
	public function getManager() {
		return $this->manager;
	}

	/**
	 * get the session object
	 *
	 * @return ISession
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * set the session object
	 *
	 * @param ISession $session
	 */
	public function setSession(ISession $session) {
		if ($this->session instanceof ISession) {
			$this->session->close();
		}
		$this->session = $session;
		$this->activeUser = null;
	}

	/**
	 * set the currently active user
	 *
	 * @param User|null $user
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
	 * @return IUser|null Current user, otherwise null
	 */
	public function getUser() {
		// FIXME: This is a quick'n dirty work-around for the incognito mode as
		// described at https://github.com/owncloud/core/pull/12912#issuecomment-67391155
		if (OC_User::isIncognitoMode()) {
			return null;
		}
		if (is_null($this->activeUser)) {
			$uid = $this->session->get('user_id');
			if (is_null($uid)) {
				return null;
			}
			$this->activeUser = $this->manager->get($uid);
			if (is_null($this->activeUser)) {
				return null;
			}
			$this->validateSession($this->activeUser);
		}
		return $this->activeUser;
	}

	protected function validateSession(IUser $user) {
		try {
			$sessionId = $this->session->getId();
		} catch (SessionNotAvailableException $ex) {
			return;
		}
		try {
			$token = $this->tokenProvider->getToken($sessionId);
		} catch (InvalidTokenException $ex) {
			// Session was invalidated
			$this->logout();
			return;
		}

		// Check whether login credentials are still valid and the user was not disabled
		// This check is performed each 5 minutes
		$lastCheck = $this->session->get('last_login_check') ? : 0;
		$now = $this->timeFacory->getTime();
		if ($lastCheck < ($now - 60 * 5)) {
			try {
				$pwd = $this->tokenProvider->getPassword($token, $sessionId);
			} catch (InvalidTokenException $ex) {
				// An invalid token password was used -> log user out
				$this->logout();
				return;
			}

			if ($this->manager->checkPassword($token->getLoginName(), $pwd) === false
				|| !$user->isEnabled()) {
				// Password has changed or user was disabled -> log user out
				$this->logout();
				return;
			}
			$this->session->set('last_login_check', $now);
		}

		// Session is valid, so the token can be refreshed
		$this->updateToken($token);
	}

	/**
	 * Checks whether the user is logged in
	 *
	 * @return bool if logged in
	 */
	public function isLoggedIn() {
		$user = $this->getUser();
		if (is_null($user)) {
			return false;
		}

		return $user->isEnabled();
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
		$this->session->regenerateId();
		if ($this->validateToken($password)) {
			$user = $this->getUser();

			// When logging in with token, the password must be decrypted first before passing to login hook
			try {
				$token = $this->tokenProvider->getToken($password);
				$password = $this->tokenProvider->getPassword($token, $password);
				$this->manager->emit('\OC\User', 'preLogin', array($uid, $password));
			} catch (InvalidTokenException $ex) {
				// Invalid token, nothing to do
			}
		} else {
			$this->manager->emit('\OC\User', 'preLogin', array($uid, $password));
			$user = $this->manager->checkPassword($uid, $password);
		}
		if ($user !== false) {
			if (!is_null($user)) {
				if ($user->isEnabled()) {
					$this->setUser($user);
					$this->setLoginName($uid);
					$this->manager->emit('\OC\User', 'postLogin', array($user, $password));
					if ($this->isLoggedIn()) {
						$this->prepareUserLogin();
						return true;
					} else {
						// injecting l10n does not work - there is a circular dependency between session and \OCP\L10N\IFactory
						$message = \OC::$server->getL10N('lib')->t('Login canceled by app');
						throw new LoginException($message);
					}
				} else {
					// injecting l10n does not work - there is a circular dependency between session and \OCP\L10N\IFactory
					$message = \OC::$server->getL10N('lib')->t('User disabled');
					throw new LoginException($message);
				}
			}
		}
		return false;
	}

	protected function prepareUserLogin() {
		// TODO: mock/inject/use non-static
		// Refresh the token
		\OC::$server->getCsrfTokenManager()->refreshToken();
		//we need to pass the user name, which may differ from login name
		$user = $this->getUser()->getUID();
		OC_Util::setupFS($user);
		//trigger creation of user home and /files folder
		\OC::$server->getUserFolder($user);
	}

	/**
	 * Tries to login the user with HTTP Basic Authentication
	 *
	 * @todo do not allow basic auth if the user is 2FA enforced
	 * @param IRequest $request
	 * @return boolean if the login was successful
	 */
	public function tryBasicAuthLogin(IRequest $request) {
		if (!empty($request->server['PHP_AUTH_USER']) && !empty($request->server['PHP_AUTH_PW'])) {
			$result = $this->login($request->server['PHP_AUTH_USER'], $request->server['PHP_AUTH_PW']);
			if ($result === true) {
				/**
				 * Add DAV authenticated. This should in an ideal world not be
				 * necessary but the iOS App reads cookies from anywhere instead
				 * only the DAV endpoint.
				 * This makes sure that the cookies will be valid for the whole scope
				 * @see https://github.com/owncloud/core/issues/22893
				 */
				$this->session->set(
					Auth::DAV_AUTHENTICATED, $this->getUser()->getUID()
				);
				return true;
			}
		}
		return false;
	}

	private function loginWithToken($uid) {
		// TODO: $this->manager->emit('\OC\User', 'preTokenLogin', array($uid));
		$user = $this->manager->get($uid);
		if (is_null($user)) {
			// user does not exist
			return false;
		}
		if (!$user->isEnabled()) {
			// disabled users can not log in
			return false;
		}

		//login
		$this->setUser($user);
		// TODO: $this->manager->emit('\OC\User', 'postTokenLogin', array($user));
		return true;
	}

	/**
	 * Create a new session token for the given user credentials
	 *
	 * @param IRequest $request
	 * @param string $uid user UID
	 * @param string $loginName login name
	 * @param string $password
	 * @return boolean
	 */
	public function createSessionToken(IRequest $request, $uid, $loginName, $password) {
		if (is_null($this->manager->get($uid))) {
			// User does not exist
			return false;
		}
		$name = isset($request->server['HTTP_USER_AGENT']) ? $request->server['HTTP_USER_AGENT'] : 'unknown browser';
		try {
			$sessionId = $this->session->getId();
			$this->tokenProvider->generateToken($sessionId, $uid, $loginName, $password, $name);
		} catch (SessionNotAvailableException $ex) {

		}
		return true;
	}

	/**
	 * @param string $token
	 * @return boolean
	 */
	private function validateToken($token) {
		try {
			$token = $this->tokenProvider->validateToken($token);
			if (!is_null($token)) {
				$result = $this->loginWithToken($token->getUID());
				if ($result) {
					// Login success
					$this->updateToken($token);
					return true;
				}
			}
		} catch (InvalidTokenException $ex) {

		}
		return false;
	}

	/**
	 * @param IToken $token
	 */
	private function updateToken(IToken $token) {
		// To save unnecessary DB queries, this is only done once a minute
		$lastTokenUpdate = $this->session->get('last_token_update') ? : 0;
		$now = $this->timeFacory->getTime();
		if ($lastTokenUpdate < ($now - 60)) {
			$this->tokenProvider->updateToken($token);
			$this->session->set('last_token_update', $now);
		}
	}

	/**
	 * Tries to login the user with auth token header
	 *
	 * @todo check remember me cookie
	 * @return boolean
	 */
	public function tryTokenLogin(IRequest $request) {
		$authHeader = $request->getHeader('Authorization');
		if (strpos($authHeader, 'token ') === false) {
			// No auth header, let's try session id
			try {
				$sessionId = $this->session->getId();
				return $this->validateToken($sessionId);
			} catch (SessionNotAvailableException $ex) {
				return false;
			}
		} else {
			$token = substr($authHeader, 6);
			return $this->validateToken($token);
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
		$this->session->regenerateId();
		$this->manager->emit('\OC\User', 'preRememberedLogin', array($uid));
		$user = $this->manager->get($uid);
		if (is_null($user)) {
			// user does not exist
			return false;
		}

		// get stored tokens
		$tokens = OC::$server->getConfig()->getUserKeys($uid, 'login_token');
		// test cookies token against stored tokens
		if (!in_array($currentToken, $tokens, true)) {
			return false;
		}
		// replace successfully used token with a new one
		OC::$server->getConfig()->deleteUserValue($uid, 'login_token', $currentToken);
		$newToken = OC::$server->getSecureRandom()->generate(32);
		OC::$server->getConfig()->setUserValue($uid, 'login_token', $newToken, time());
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
		$user = $this->getUser();
		if (!is_null($user)) {
			try {
				$this->tokenProvider->invalidateToken($this->session->getId());
			} catch (SessionNotAvailableException $ex) {
				
			}
		}
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
		$secureCookie = OC::$server->getRequest()->getServerProtocol() === 'https';
		$expires = time() + OC::$server->getConfig()->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		setcookie('oc_username', $username, $expires, OC::$WEBROOT, '', $secureCookie, true);
		setcookie('oc_token', $token, $expires, OC::$WEBROOT, '', $secureCookie, true);
		setcookie('oc_remember_login', '1', $expires, OC::$WEBROOT, '', $secureCookie, true);
	}

	/**
	 * Remove cookie for "remember username"
	 */
	public function unsetMagicInCookie() {
		//TODO: DI for cookies and IRequest
		$secureCookie = OC::$server->getRequest()->getServerProtocol() === 'https';

		unset($_COOKIE['oc_username']); //TODO: DI
		unset($_COOKIE['oc_token']);
		unset($_COOKIE['oc_remember_login']);
		setcookie('oc_username', '', time() - 3600, OC::$WEBROOT, '', $secureCookie, true);
		setcookie('oc_token', '', time() - 3600, OC::$WEBROOT, '', $secureCookie, true);
		setcookie('oc_remember_login', '', time() - 3600, OC::$WEBROOT, '', $secureCookie, true);
		// old cookies might be stored under /webroot/ instead of /webroot
		// and Firefox doesn't like it!
		setcookie('oc_username', '', time() - 3600, OC::$WEBROOT . '/', '', $secureCookie, true);
		setcookie('oc_token', '', time() - 3600, OC::$WEBROOT . '/', '', $secureCookie, true);
		setcookie('oc_remember_login', '', time() - 3600, OC::$WEBROOT . '/', '', $secureCookie, true);
	}

}
