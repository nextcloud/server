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

namespace OC\Authentication\ClientLogin;

use OC\Authentication\Exceptions\ClientLoginPendingException;
use OC\Authentication\Exceptions\InvalidAccessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IUser;
use OCP\Security\ISecureRandom;

class Coordinator implements IClientLoginCoordinator {

	/** @var AccessTokenMapper */
	private $mapper;

	/** @var IProvider */
	private $tokenProvider;

	/** @var ISecureRandom */
	private $random;

	/** @var IConfig */
	private $config;

	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param AccessTokenMapper $mapper
	 * @param IProvider $tokenProvider
	 * @param ISecureRandom $random
	 * @param IConfig $config
	 * @param ITimeFactory $timeFactory
	 */
	public function __construct(AccessTokenMapper $mapper, IProvider $tokenProvider, ISecureRandom $random,
		IConfig $config, ITimeFactory $timeFactory) {
		$this->mapper = $mapper;
		$this->tokenProvider = $tokenProvider;
		$this->random = $random;
		$this->config = $config;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $name client name
	 * @return string new access token to identify async login process
	 */
	public function startClientLogin($name) {
		$token = $this->random->generate(128);
		$hashedToken = $this->hashToken($token);

		$accessToken = new AccessToken();
		$accessToken->setToken($hashedToken);
		$accessToken->setClientName($name);
		$accessToken->setStatus(AccessToken::STATUS_PENDING);
		$accessToken->setCreatedAt($this->timeFactory->getTime());

		$this->mapper->insert($accessToken);

		return $token;
	}

	/**
	 * @param string $accessToken
	 * @param IUser $user
	 * @throws InvalidAccessTokenException
	 */
	public function finishClientLogin($accessToken, IUser $user) {
		$hashedToken = $this->hashToken($accessToken);
		try {
			$dbToken = $this->mapper->getToken($hashedToken);
		} catch (DoesNotExistException $ex) {
			throw new InvalidAccessTokenException();
		}

		$dbToken->setStatus(AccessToken::STATUS_FINISHED);
		$dbToken->setUid($user->getUID());
		$this->mapper->update($dbToken);
	}

	/**
	 * @param string $accessToken
	 * @throws InvalidAccessTokenException
	 * @throws ClientLoginPendingException
	 * @return string
	 */
	public function getClientToken($accessToken) {
		$hashedToken = $this->hashToken($accessToken);
		try {
			$dbToken = $this->mapper->getToken($hashedToken);
		} catch (DoesNotExistException $ex) {
			throw new InvalidAccessTokenException();
		}

		// TODO: limit token age and check it here

		if (((int) $dbToken->getStatus()) === AccessToken::STATUS_PENDING) {
			// Login process pending
			throw new ClientLoginPendingException();
		}

		$this->mapper->delete($dbToken);
		return $this->createDeviceToken($dbToken);
	}

	private function hashToken($token) {
		$secret = $this->config->getSystemValue('secret');
		return hash('sha512', $token . $secret);
	}

	/**
	 * @param AccessToken $accessToken
	 */
	private function createDeviceToken(AccessToken $accessToken) {
		$token = $this->random->generate(128);
		// TODO: find a way to get loginname+password
		// TODO: what if there is no password? e.g. shibboleth SSO…
		$this->tokenProvider->generateToken($token, $accessToken->getUid(), '?', '?', $accessToken->getClientName(), IToken::PERMANENT_TOKEN);
		return $token;
	}

}
