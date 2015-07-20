<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace OC\Session;

use OCP\IConfig;
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

class CryptoWrapper {
	const COOKIE_NAME = 'oc_sessionPassphrase';

	/** @var ISession */
	protected $session;

	/** @var \OCP\Security\ICrypto */
	protected $crypto;

	/** @var ISecureRandom */
	protected $random;

	/**
	 * @param IConfig $config
	 * @param ICrypto $crypto
	 * @param ISecureRandom $random
	 */
	public function __construct(IConfig $config, ICrypto $crypto, ISecureRandom $random) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->random = $random;

		if (isset($_COOKIE[self::COOKIE_NAME])) {
			// TODO circular dependency
//			$request = \OC::$server->getRequest();
//			$this->passphrase = $request->getCookie(self::COOKIE_NAME);
			$this->passphrase = $_COOKIE[self::COOKIE_NAME];
		} else {
			$this->passphrase = $this->random->getMediumStrengthGenerator()->generate(128);

			// TODO circular dependency
			// $secureCookie = \OC::$server->getRequest()->getServerProtocol() === 'https';
			$secureCookie = false;
			$expires = time() + $this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);

			if (!defined('PHPUNIT_RUN')) {
				setcookie(self::COOKIE_NAME, $this->passphrase, $expires, \OC::$WEBROOT, '', $secureCookie);
			}
		}
	}

	/**
	 * @param ISession $session
	 * @return ISession
	 */
	public function wrapSession(ISession $session) {
		if (!($session instanceof CryptoSessionData) &&  $this->config->getSystemValue('encrypt.session', false)) {
			return new \OC\Session\CryptoSessionData($session, $this->crypto, $this->passphrase);
		}

		return $session;
	}
}
