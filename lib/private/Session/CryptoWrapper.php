<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Session;

use OCP\IRequest;
use OCP\ISession;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;

/**
 * Class CryptoWrapper provides some rough basic level of additional security by
 * storing the session data in an encrypted form.
 *
 * The content of the session is encrypted using another cookie sent by the browser.
 * One should note that an adversary with access to the source code or the system
 * memory is still able to read the original session ID from the users' request.
 * This thus can not be considered a strong security measure one should consider
 * it as an additional small security obfuscation layer to comply with compliance
 * guidelines.
 *
 * TODO: Remove this in a future release with an approach such as
 * https://github.com/owncloud/core/pull/17866
 *
 * @package OC\Session
 */
class CryptoWrapper {
	/** @var string */
	public const COOKIE_NAME = 'oc_sessionPassphrase';

	protected string $passphrase;

	public function __construct(
		protected ICrypto $crypto,
		ISecureRandom $random,
		IRequest $request,
	) {
		$passphrase = $request->getCookie(self::COOKIE_NAME);
		if ($passphrase === null) {
			$passphrase = $random->generate(128);
			$secureCookie = $request->getServerProtocol() === 'https';
			// FIXME: Required for CI
			if (!defined('PHPUNIT_RUN')) {
				$webRoot = \OC::$WEBROOT;
				if ($webRoot === '') {
					$webRoot = '/';
				}

				setcookie(
					self::COOKIE_NAME,
					$passphrase,
					[
						'expires' => 0,
						'path' => $webRoot,
						'domain' => \OCP\Server::get(\OCP\IConfig::class)->getSystemValueString('cookie_domain'),
						'secure' => $secureCookie,
						'httponly' => true,
						'samesite' => 'Lax',
					]
				);
			}
		}
		$this->passphrase = $passphrase;
	}

	public function wrapSession(ISession $session): ISession {
		if (!($session instanceof CryptoSessionData)) {
			return new CryptoSessionData($session, $this->crypto, $this->passphrase);
		}

		return $session;
	}
}
