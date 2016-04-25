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

	public function __construct(DefaultTokenMapper $mapper, ICrypto $crypto,
		IConfig $config, ILogger $logger) {
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
		$secret = $this->config->getSystemValue('secret');
		$dbToken->setPassword($this->crypto->encrypt($password . $secret));
		$dbToken->setName($name);
		$dbToken->setToken(hash('sha512', $token));

		$this->mapper->insert($dbToken);

		return $dbToken;
	}

	/**
	 * @param string $token
	 * @throws InvalidTokenException
	 * @return string user UID
	 */
	public function validateToken($token) {
		$this->logger->debug('validating default token <' . $token . '>');
		try {
			$dbToken = $this->mapper->getTokenUser(hash('sha512', $token));
			$this->logger->debug('valid token for ' . $dbToken->getUid());
			return $dbToken->getUid();
		} catch (DoesNotExistException $ex) {
			$this->logger->warning('invalid token');
			throw new InvalidTokenException();
		}
	}

}
