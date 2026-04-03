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

/**
 * Coordinates the Login Flow v2 token exchange.
 *
 * A flow stores temporary state for a login session, including a per-flow key pair.
 * The app password is encrypted with the flow's public key and can later be recovered
 * only by a client that holds the poll token needed to unlock the stored private key.
 */
class LoginFlowV2Service {
	public function __construct(
		private readonly LoginFlowV2Mapper $mapper,
		private readonly ISecureRandom $random,
		private readonly ITimeFactory $time,
		private readonly IConfig $config,
		private readonly ICrypto $crypto,
		private readonly LoggerInterface $logger,
		private readonly IProvider $tokenProvider,
	) {
	}

	/**
	 * Returns the credentials for a completed login flow.
	 *
	 * The poll token is a one-time secret held by the client. It is used to look up
	 * the flow and unlock the private key needed to decrypt the stored app password.
	 * Once the credentials are available, the flow is consumed so the poll token
	 * cannot be used again.
	 *
	 * @throws LoginFlowV2NotFoundException
	 */
	public function poll(string $pollToken): LoginFlowV2Credentials {
		try {
			$flow = $this->mapper->getByPollToken($this->hashToken($pollToken));
		} catch (DoesNotExistException $e) {
			throw new LoginFlowV2NotFoundException('Invalid token');
		}

		$loginName = $flow->getLoginName();
		$server = $flow->getServer();
		$appPassword = $flow->getAppPassword();

		if ($loginName === null || $server === null || $appPassword === null) {
			throw new LoginFlowV2NotFoundException('Token not yet ready');
		}

		// Consume the flow so the poll token can only be used once.
		$this->mapper->delete($flow);

		try {
			// Decrypt the stored private key using the poll token.
			$unlockedPrivateKey = $this->crypto->decrypt($flow->getPrivateKey(), $pollToken);
		} catch (\Exception) {
			throw new LoginFlowV2NotFoundException('Private key could not be decrypted');
		}

		// Decrypt the stored app password using the decrypted private key.
		$decryptedAppPassword = $this->decryptPassword($flow->getAppPassword(), $unlockedPrivateKey);
		if ($decryptedAppPassword === null) {
			throw new LoginFlowV2NotFoundException('App password could not be decrypted');
		}

		return new LoginFlowV2Credentials($server, $loginName, $decryptedAppPassword);
	}

	/**
	 * @param string $loginToken
	 * @return LoginFlowV2
	 * @throws LoginFlowV2NotFoundException
	 * @throws LoginFlowV2ClientForbiddenException
	 */
	public function getByLoginToken(string $loginToken): LoginFlowV2 {
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
	 * Marks the login flow as started.
	 *
	 * Returns false if the login token does not exist.
	 */
	public function startLoginFlow(string $loginToken): bool {
		try {
			$flow = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		$flow->setStarted(1);
		$this->mapper->update($flow);

		return true;
	}

	/**
	 * Completes the login flow by generating an app password for the authenticated session
	 * and storing it encrypted with the flow's public key.
	 *
	 * Returns false if the login token or session token is invalid.
	 */
	public function flowDone(string $loginToken, string $sessionId, string $server, string $userId): bool {
		try {
			$flow = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		try {
			$sessionToken = $this->tokenProvider->getToken($sessionId);
			$loginName = $sessionToken->getLoginName();
			try {
				$password = $this->tokenProvider->getPassword($sessionToken, $sessionId);
			} catch (PasswordlessTokenException $ex) {
				// Some session tokens are passwordless, so no login password can be reused here.
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
			$flow->getClientName(),
			IToken::PERMANENT_TOKEN,
			IToken::DO_NOT_REMEMBER
		);

		$flow->setLoginName($loginName);
		$flow->setServer($server);

		$encryptedAppPassword = $this->encryptPassword($appPassword, $flow->getPublicKey());
		$flow->setAppPassword($encryptedAppPassword);

		$this->mapper->update($flow);
		return true;
	}

	/**
	 * Completes the login flow with an app password that has already been created by the caller
	 * and storing it encrypted with the flow's public key.
	 *
	 * Returns false if the login token does not exist.
	 */
	public function flowDoneWithAppPassword(string $loginToken, string $server, string $loginName, string $appPassword): bool {
		try {
			$flow = $this->mapper->getByLoginToken($loginToken);
		} catch (DoesNotExistException $e) {
			return false;
		}

		$flow->setLoginName($loginName);
		$flow->setServer($server);

		$encryptedAppPassword = $this->encryptPassword($appPassword, $flow->getPublicKey());
		$flow->setAppPassword($encryptedAppPassword);

		$this->mapper->update($flow);
		return true;
	}

	/**
	 * Creates a new login flow with fresh poll and login tokens and a dedicated key pair.
	 *
	 * The poll token is returned only to the polling client. The generated private key is
	 * encrypted with that poll token, and the corresponding public key is later used to
	 * encrypt the app password before it is stored in the flow.
	 */
	public function createTokens(string $userAgent): LoginFlowV2Tokens {
		$flow = new LoginFlowV2();
		$pollToken = $this->random->generate(128, ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER);
		$loginToken = $this->random->generate(128, ISecureRandom::CHAR_DIGITS . ISecureRandom::CHAR_LOWER . ISecureRandom::CHAR_UPPER);

		// Store the poll token only as a hash because it is later presented as a bearer secret.
		// The login token must remain retrievable in plain form because it is looked up directly.
		$flow->setPollToken($this->hashToken($pollToken));
		$flow->setLoginToken($loginToken);
		$flow->setStarted(0);
		$flow->setTimestamp($this->time->getTime());
		$flow->setClientName($userAgent);

		['publicKey' => $publicKey, 'privateKey' => $privateKey] = $this->getKeyPair();
		$encryptedPrivateKey = $this->crypto->encrypt($privateKey, $pollToken);

		$flow->setPublicKey($publicKey);
		$flow->setPrivateKey($encryptedPrivateKey);

		$this->mapper->insert($flow);

		return new LoginFlowV2Tokens($loginToken, $pollToken);
	}

	/**
	 * Hashes a poll token with the instance secret before persisting or looking it up.
	 */
	private function hashToken(string $token): string {
		// Intentionally no default: the instance secret must be configured.
		$secret = $this->config->getSystemValue('secret');
		return hash('sha512', $token . $secret);
	}

	/**
	 * Generates an RSA key pair for encrypting the app password during the flow.
	 *
	 * @return array{publicKey: string, privateKey: string}
	 */
	private function getKeyPair(): array {
		$config = array_merge([
			'digest_alg' => 'sha512',
			'private_key_bits' => 2048,
		], $this->config->getSystemValue('openssl', []));

		// Generate a fresh RSA key pair for this flow.
		$keyPair = openssl_pkey_new($config);
		if ($keyPair === false) {
			$this->logOpensslError();
			throw new \RuntimeException('Could not initialize keys');
		}

		if (openssl_pkey_export($keyPair, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		// Extract the PEM-encoded public key from the generated key pair.
		$publicKeyDetails = openssl_pkey_get_details($keyPair);
		$publicKey = $publicKeyDetails['key'];

		return [
			'publicKey' => $publicKey,
			'privateKey' => $privateKey,
		];
	}

	private function logOpensslError(): void {
		// Drain the OpenSSL error queue so the root cause is visible in server logs.
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something is wrong with your openssl setup: ' . implode(', ', $errors));
	}

	private function encryptPassword(string $password, string $publicKey): string {
		openssl_public_encrypt($password, $encryptedPassword, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
		$encoded = base64_encode($encryptedPassword);

		return $encoded;
	}

	private function decryptPassword(string $encryptedPassword, string $privateKey): ?string {
		$decoded = base64_decode($encryptedPassword);
		$success = openssl_private_decrypt($decoded, $password, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

		return $success ? $password : null;
	}
}
