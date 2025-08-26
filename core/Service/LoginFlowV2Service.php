<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Service;

use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Token\IProvider;
use OC\Authentication\Token\IToken;
use OC\Core\Data\LoginFlowV2Credentials;
use OC\Core\Data\LoginFlowV2Tokens;
use OC\Core\Db\LoginFlowV2;
use OC\Core\Db\LoginFlowV2Mapper;
use OC\Core\Exception\LoginFlowV2ClientForbiddenException;
use OC\Core\Exception\LoginFlowV2NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\IConfig;
use OCP\Security\ICrypto;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class LoginFlowV2Service {
	public function __construct(
		private LoginFlowV2Mapper $mapper,
		private ISecureRandom $random,
		private ITimeFactory $time,
		private IConfig $config,
		private ICrypto $crypto,
		private LoggerInterface $logger,
		private IProvider $tokenProvider,
	) {
	}

	/**
	 * @param string $pollToken
	 * @return LoginFlowV2Credentials
	 * @throws LoginFlowV2NotFoundException
	 */
	public function poll(string $pollToken): LoginFlowV2Credentials {
		try {
			$data = $this->mapper->getByPollToken($this->hashToken($pollToken));
		} catch (DoesNotExistException $e) {
			throw new LoginFlowV2NotFoundException('Invalid token');
		}

		$loginName = $data->getLoginName();
		$server = $data->getServer();
		$appPassword = $data->getAppPassword();

		if ($loginName === null || $server === null || $appPassword === null) {
			throw new LoginFlowV2NotFoundException('Token not yet ready');
		}

		// Remove the data from the DB
		$this->mapper->delete($data);

		try {
			// Decrypt the apptoken
			$privateKey = $this->crypto->decrypt($data->getPrivateKey(), $pollToken);
		} catch (\Exception) {
			throw new LoginFlowV2NotFoundException('Apptoken could not be decrypted');
		}

		$appPassword = $this->decryptPassword($data->getAppPassword(), $privateKey);
		if ($appPassword === null) {
			throw new LoginFlowV2NotFoundException('Apptoken could not be decrypted');
		}

		return new LoginFlowV2Credentials($server, $loginName, $appPassword);
	}

	/**
	 * @param string $loginToken
	 * @return LoginFlowV2
	 * @throws LoginFlowV2NotFoundException
	 * @throws LoginFlowV2ClientForbiddenException
	 */
	public function getByLoginToken(string $loginToken): LoginFlowV2 {
		/** @var LoginFlowV2|null $flow */
		$flow = null;

		try {
			$flow = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			throw new LoginFlowV2NotFoundException('Login token invalid');
		}

		$allowedAgents = $this->config->getSystemValue('core.login_flow_v2.allowed_user_agents', []);

		if (empty($allowedAgents)) {
			return $flow;
		}

		$flowClient = $flow->getClientName();

		foreach ($allowedAgents as $allowedAgent) {
			if (preg_match($allowedAgent, $flowClient) === 1) {
				return $flow;
			}
		}

		throw new LoginFlowV2ClientForbiddenException('Client not allowed');
	}

	/**
	 * @param string $loginToken
	 * @return bool returns true if the start was successfull. False if not.
	 */
	public function startLoginFlow(string $loginToken): bool {
		try {
			$data = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		$data->setStarted(1);
		$this->mapper->update($data);

		return true;
	}

	/**
	 * @param string $loginToken
	 * @param string $sessionId
	 * @param string $server
	 * @param string $userId
	 * @return bool true if the flow was successfully completed false otherwise
	 */
	public function flowDone(string $loginToken, string $sessionId, string $server, string $userId): bool {
		try {
			$data = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
			$loginName = $sessionToken->getLoginName();
			try {
				$password = $this->tokenProvider->getPassword($sessionToken, $sessionId);
			} catch (PasswordlessTokenException $ex) {
				$password = null;
			}
		} catch (InvalidTokenException $ex) {
			return false;
		}

		$appPassword = $this->random->generate(72, ISecureRandom::CHAR_UPPER . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_DIGITS);
		$this->tokenProvider->generateToken(
			$appPassword,
			$userId,
			$loginName,
			$password,
			$data->getClientName(),
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		$data->setLoginName($loginName);
		$data->setServer($server);

		// Properly encrypt
		$data->setAppPassword($this->encryptPassword($appPassword, $data->getPublicKey()));

		$this->mapper->update($data);
		return true;
	}

	public function flowDoneWithAppPassword(string $loginToken, string $server, string $loginName, string $appPassword): bool {
		try {
			$data = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		$data->setLoginName($loginName);
		$data->setServer($server);

		// Properly encrypt
		$data->setAppPassword($this->encryptPassword($appPassword, $data->getPublicKey()));

		$this->mapper->update($data);
		return true;
	}

	public function createTokens(string $userAgent): LoginFlowV2Tokens {
		$flow = new LoginFlowV2();
		$pollToken = $this->random->generate(128, ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER);
		$loginToken = $this->random->generate(128, ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER);
		$flow->setPollToken($this->hashToken($pollToken));
		$flow->setLoginToken($loginToken);
		$flow->setStarted(0);
		$flow->setTimestamp($this->time->getTime());
		$flow->setClientName($userAgent);

		[$publicKey, $privateKey] = $this->getKeyPair();
		$privateKey = $this->crypto->encrypt($privateKey, $pollToken);

		$flow->setPublicKey($publicKey);
		$flow->setPrivateKey($privateKey);

		$this->mapper->insert($flow);

		return new LoginFlowV2Tokens($loginToken, $pollToken);
	}

	private function hashToken(string $token): string {
		$secret = $this->config->getSystemValue('secret');
		return hash('sha512', $token . $secret);
	}

	private function getKeyPair(): array {
		$config = array_merge([
			'digest_alg' => 'sha512',
			'private_key_bits' => 2048,
		], $this->config->getSystemValue('openssl', []));

		// Generate new key
		$res = openssl_pkey_new($config);
		if ($res === false) {
			$this->logOpensslError();
			throw new \RuntimeException('Could not initialize keys');
		}

		if (openssl_pkey_export($res, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		// Extract the public key from $res to $pubKey
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];

		return [$publicKey, $privateKey];
	}

	private function logOpensslError(): void {
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something is wrong with your openssl setup: ' . implode(', ', $errors));
	}

	private function encryptPassword(string $password, string $publicKey): string {
		openssl_public_encrypt($password, $encryptedPassword, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
		$encryptedPassword = base64_encode($encryptedPassword);

		return $encryptedPassword;
	}

	private function decryptPassword(string $encryptedPassword, string $privateKey): ?string {
		$encryptedPassword = base64_decode($encryptedPassword);
		$success = openssl_private_decrypt($encryptedPassword, $password, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

		return $success ? $password : null;
	}
}
