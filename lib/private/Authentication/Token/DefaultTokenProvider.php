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

	/**
	 * @param DefaultTokenMapper $mapper
	 * @param ICrypto $crypto
	 * @param IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(DefaultTokenMapper $mapper, ICrypto $crypto, IConfig $config, ILogger $logger) {
		$this->mapper = $mapper;
		$this->crypto = $crypto;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Create and persist a new token
	 *
	 * @param string $token
	 * @param string $uid
	 * @param string $password
	 * @return DefaultToken
	 */
	public function generateToken($token, $uid, $password, $name) {
		$dbToken = new DefaultToken();
		$dbToken->setUid($uid);
		$dbToken->setPassword($this->encryptPassword($password, $token));
		$dbToken->setName($name);
		$dbToken->setToken($this->hashToken($token));
		$dbToken->setLastActivity(time());

		$this->mapper->insert($dbToken);

		return $dbToken;
	}

	/**
	 * Update token activity timestamp
	 *
	 * @param DefaultToken $token
	 */
	public function updateToken(DefaultToken $token) {
		$token->setLastActivity(time());

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
		$olderThan = time() - (int) $this->config->getSystemValue('session_lifetime', 60 * 60 * 24);
		$this->logger->info('Invalidating tokens older than ' . date('c', $olderThan));
		$this->mapper->invalidateOld($olderThan);
	}

	/**
	 * @param string $token
	 * @throws InvalidTokenException
	 * @return string user UID
	 */
	public function validateToken($token) {
		$this->logger->debug('validating default token <' . $token . '>');
		try {
			$dbToken = $this->mapper->getToken($this->hashToken($token));
			$this->logger->debug('valid token for ' . $dbToken->getUid());
			return $dbToken->getUid();
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
		return hash('sha512', $token);
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
