<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\User\Authentication\RememberMe;

use OCP\IConfig;
use OCP\ILogger;
use OCP\IRequest;
use OCP\Security\ICrypto;

/**
 * Class RememberMe
 *
 * @package OC\User\Authentication
 */
class RememberMe {
	/** @var ILogger */
	private $logger;
	/** @var IConfig */
	private $config;
	/** @var IRequest */
	private $request;
	/** @var ICrypto */
	private $crypto;

	/**
	 * @param ILogger $logger
	 * @param IConfig $config
	 * @param IRequest $request
	 * @param ICrypto $crypto
	 */
	public function __construct(ILogger $logger,
								IConfig $config,
								IRequest $request,
								ICrypto $crypto) {
		$this->logger = $logger;
		$this->config = $config;
		$this->request = $request;
		$this->crypto = $crypto;
	}

	/**
	 * Whether remember me login is allowed, single applications may forbid a
	 * remember me login for example when an authentication without password is
	 * not feasible as applications may need to intercept the user password for
	 * functionality. (e.g. the files_external application)
	 *
	 * @return bool
	 */
	private function isAllowed() {
		return true;
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
	 * Remove outdated and therefore invalid tokens for a user
	 * @param string $user
	 */
	public function pruneOutdatedTokens($user) {
		$cutoff = time() - $this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$tokens = $this->config->getUserKeys($user, 'login_token');
		foreach ($tokens as $token) {
			$time = $this->config->getUserValue($user, 'login_token', $token);
			if ($time < $cutoff) {
				$this->config->deleteUserValue($user, 'login_token', $token);
			}
		}
	}

	/**
	 * Set cookie value to use in next page load
	 *
	 * @param string $username username to be set
	 * @param string $token
	 */
	public function setMagicInCookie($username, $token) {
		$secureCookie = $this->request->getServerProtocol() === 'https';
		$expires = time() + $this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);

		setcookie("oc_username", $username, $expires, \OC::$WEBROOT, '', $secureCookie, true);
		setcookie("oc_token", $token, $expires, \OC::$WEBROOT, '', $secureCookie, true);
		setcookie("oc_remember_login", "1", $expires, \OC::$WEBROOT, '', $secureCookie, true);
	}

	/**
	 * Remove cookie for "remember username"
	 */
	public function unsetMagicInCookie() {
		$secureCookie = $this->request->getServerProtocol() === 'https';

		unset($_COOKIE["oc_username"]); //TODO: DI
		unset($_COOKIE["oc_token"]);
		unset($_COOKIE["oc_remember_login"]);
		setcookie('oc_username', '', time() - 3600, \OC::$WEBROOT, '',$secureCookie, true);
		setcookie('oc_token', '', time() - 3600, \OC::$WEBROOT, '', $secureCookie, true);
		setcookie('oc_remember_login', '', time() - 3600, \OC::$WEBROOT, '', $secureCookie, true);
		// old cookies might be stored under /webroot/ instead of /webroot
		// and Firefox doesn't like it!
		setcookie('oc_username', '', time() - 3600, \OC::$WEBROOT . '/', '', $secureCookie, true);
		setcookie('oc_token', '', time() - 3600, \OC::$WEBROOT . '/', '', $secureCookie, true);
		setcookie('oc_remember_login', '', time() - 3600, \OC::$WEBROOT . '/', '', $secureCookie, true);
	}

}
