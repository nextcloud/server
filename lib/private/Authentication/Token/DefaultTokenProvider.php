<?php

/**
 * @author Christoph Wurst <christoph@owncloud.com>
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

namespace OC\Authentication\Token;

use OC\Authentication\Exceptions\InvalidTokenException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Security\ICrypto;

class DefaultTokenProvider implements IProvider {

	/** @var DefaultTokenMapper */
	private $mapper;

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ILogger $logger */
	private $logger;

	/** @var ITimeFactory $time */
	private $time;

	/**
	 * @param DefaultTokenMapper $mapper
	 * @param ICrypto $crypto
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(DefaultTokenMapper $mapper, ICrypto $crypto, IConfig $config, ILogger $logger, ITimeFactory $time) {
		$this->mapper = $mapper;
		$this->crypto = $crypto;
		$this->config = $config;
		$this->logger = $logger;
		$this->time = $time;
	}

	/**
	 * Create and persist a new token
	 *
	 * @param string $token
	 * @param string $uid
	 * @param string $password
	 * @param int $type token type
	 * @return DefaultToken
	 */
	public function generateToken($token, $uid, $password, $name, $type = IToken::TEMPORARY_TOKEN) {
		$dbToken = new DefaultToken();
		$dbToken->setUid($uid);
		$dbToken->setPassword($this->encryptPassword($password, $token));
		$dbToken->setName($name);
		$dbToken->setToken($this->hashToken($token));
		$dbToken->setType($type);
		$dbToken->setLastActivity($this->time->getTime());

		$this->mapper->insert($dbToken);

		return $dbToken;
	}

	/**
	 * Update token activity timestamp
	 *
	 * @param DefaultToken $token
	 */
	public function updateToken(IToken $token) {
		if (!($token instanceof DefaultToken)) {
			throw new InvalidTokenException();
		}
		/** @var DefaultToken $token */
		$token->setLastActivity($this->time->getTime());

		$this->mapper->update($token);
	}

	/**
	 * @param string $token
	 * @throws InvalidTokenException
	 */
	public function getToken($token) {
		try {
			return $this->mapper->getToken($this->hashToken($token));
		} catch (DoesNotExistException $ex) {
			throw new InvalidTokenException();
		}
	}

	/**
	 * @param DefaultToken $savedToken
	 * @param string $token session token
	 */
	public function getPassword(DefaultToken $savedToken, $token) {
		return $this->decryptPassword($savedToken->getPassword(), $token);
	}

	/**
	 * Invalidate (delete) the given session token
	 *
	 * @param string $token
	 */
	public function invalidateToken($token) {
		$this->mapper->invalidate($this->hashToken($token));
	}

	/**
	 * Invalidate (delete) old session tokens
	 */
	public function invalidateOldTokens() {
		$olderThan = $this->time->getTime() - (int) $this->config->getSystemValue('session_lifetime', 60 * 60 * 24);
		$this->logger->info('Invalidating tokens older than ' . date('c', $olderThan));
		$this->mapper->invalidateOld($olderThan);
	}

	/**
	 * @param string $token
	 * @throws InvalidTokenException
	 * @return IToken user UID
	 */
	public function validateToken($token) {
		$this->logger->debug('validating default token <' . $token . '>');
		try {
			$dbToken = $this->mapper->getToken($this->hashToken($token));
			$this->logger->debug('valid token for ' . $dbToken->getUid());
			return $dbToken;
		} catch (DoesNotExistException $ex) {
			$this->logger->warning('invalid token');
			throw new InvalidTokenException();
		}
	}

	/**
	 * @param string $token
	 * @return string
	 */
	private function hashToken($token) {
		$secret = $this->config->getSystemValue('secret');
		return hash('sha512', $token . $secret);
	}

	/**
	 * Encrypt the given password
	 *
	 * The token is used as key
	 *
	 * @param string $password
	 * @param string $token
	 * @return string encrypted password
	 */
	private function encryptPassword($password, $token) {
		$secret = $this->config->getSystemValue('secret');
		return $this->crypto->encrypt($password, $token . $secret);
	}

	/**
	 * Decrypt the given password
	 *
	 * The token is used as key
	 *
	 * @param string $password
	 * @param string $token
	 * @return string the decrypted key
	 */
	private function decryptPassword($password, $token) {
		$secret = $this->config->getSystemValue('secret');
		return $this->crypto->decrypt($password, $token . $secret);
	}

}
