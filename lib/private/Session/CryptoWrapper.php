<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Session;

use OCP\IConfig;
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
 * @deprecated
 */
class CryptoWrapper {
	/** @deprecated 31.0.0 */
	public const COOKIE_NAME = 'oc_sessionPassphrase';

	/** @var IConfig */
	protected $config;
	/** @var ISession */
	protected $session;
	/** @var ICrypto */
	protected $crypto;
	/** @var ISecureRandom */
	protected $random;
	/** @var string */
	protected $passphrase;

	/**
	 * @param IConfig $config
	 * @param ICrypto $crypto
	 * @param ISecureRandom $random
	 * @param IRequest $request
	 * @depreacted 31.0.0
	 */
	public function __construct(IConfig $config,
		ICrypto $crypto,
		ISecureRandom $random,
		IRequest $request) {
		$this->crypto = $crypto;
		$this->config = $config;
		$this->random = $random;

		if (!is_null($request->getCookie(self::COOKIE_NAME))) {
			$this->passphrase = $request->getCookie(self::COOKIE_NAME);
		} else {
			$this->passphrase = $this->random->generate(128);
		}
	}

	/**
	 * @param ISession $session
	 * @return ISession
	 * @deprecated 31.0.0
	 */
	public function wrapSession(ISession $session) {
		if (!($session instanceof LegacyCryptoSessionData)) {
			return new LegacyCryptoSessionData($session, $this->crypto, $this->passphrase);
		}

		return $session;
	}
}
