<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Token;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\TokenPasswordExpiredException;
use OC\Authentication\Exceptions\WipeTokenException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Authentication\Token\IToken as OCPIToken;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\IHasher;
use Psr\Log\LoggerInterface;

class PublicKeyTokenProvider implements IProvider {
	public const TOKEN_MIN_LENGTH = 22; // bytes
	private const TOKEN_CACHE_TTL = 10; // seconds
	public const TOKEN_NAME_MAX_LENGTH = 128; // characters

	use TTransactional;

	/** @var ICache */
	private $cache;

	public function __construct(
		private PublicKeyTokenMapper $mapper,
		private ICrypto $crypto,
		private IConfig $config,
		private IDBConnection $db,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private IHasher $hasher,
		ICacheFactory $cacheFactory,
	) {

		$this->cache = $cacheFactory->isLocalCacheAvailable() ? $cacheFactory->createLocal('authtoken_') : $cacheFactory->createInMemory();
	}

	/**
	 * {@inheritDoc}
	 */
	public function generateToken(
		string $token,
		string $uid,
		string $loginName,
		?string $password,
		string $name,
		int $type = OCPIToken::TEMPORARY_TOKEN,
		int $remember = OCPIToken::DO_NOT_REMEMBER,
		?array $scope = null,
	): OCPIToken {

		// Check for valid token length
		$tokenLength = strlen($token);
		if ($tokenLength < self::TOKEN_MIN_LENGTH) {
			$exception = new InvalidTokenException('Token is too short, minimum of '
												   . self::TOKEN_MIN_LENGTH
												   . ' characters is required, '
												   . $tokenLength
												   . ' characters given'
			);
			$this->logger->error('Invalid token provided when generating new token', ['exception' => $exception]);
			throw $exception;
		}

		// Trim overly long token names
		if (mb_strlen($name) > self::TOKEN_NAME_MAX_LENGTH) {
			$name = mb_substr($name, 0, 120) . '…';
		}

		// Generate a (preliminary) new token
		$dbToken = $this->newToken($token, $uid, $loginName, $password, $name, $type, $remember);
		/**
		 * TODO (perf): If we pass $password as null above (instead of the actual p/w) I think we can avoid having newToken() encrypt and
		 * hash a password we may yet overwrite... and we can then explicitly call dbToken->setPassword() and dbToken->setPasswordHash()
		 * below when/if deemed appropriate (as we already do for the latter).
		 */

		// Set the scope for the new token (if specified)
		if ($scope !== null) {
			$dbToken->setScope($scope);
		}

		// If a password was specified, determine if it matches the one already used by other tokens associated with this $uid.
		// Comparing to any single existing token (belonging to $uid) is enough since all passwords (for the same $uid) use the same hash.
		if ($password !== null && $randomOldToken = $this->mapper->getFirstTokenForUser($uid)) { // don't bother if there's no password nor other tokens
			$existingHash = $randomOldToken->getPasswordHash();
			if ($existingHash) { // only bother if there's an existing hash
				$newPasswordHash = sha1($password) . $password; // calculate hash of the new token's specified password
				$oldTokenMatches = $this->hasher->verify($newPasswordHash, $existingHash); // compare new token's password (hash) to our existing one
				if ($oldTokenMatches) {
					$dbToken->setPasswordHash($existingHash); // if the hashes match, go ahead and set the p/w hash in the new token
				}
			}
		}

		// Persist the new token
		$this->mapper->insert($dbToken);

		// Update the embedded passwords (if required) for all the (other) tokens belonging to $uid
		if (!empty($oldTokenMatches) && $password !== null) {
			$this->updatePasswords($uid, $password);
		}

		// Add the new token to the cache
		$this->cacheToken($dbToken);

		// We're done
		return $dbToken;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getToken(string $tokenId): OCPIToken {

		// Check for valid tokenId length
		/**
		 * Token length: 72
		 * @see \OC\Core\Controller\ClientFlowLoginController::generateAppPassword
		 * @see \OC\Core\Controller\AppPasswordController::getAppPassword
		 * @see \OC\Core\Command\User\AddAppPassword::execute
		 * @see \OC\Core\Service\LoginFlowV2Service::flowDone
		 * @see \OCA\Talk\MatterbridgeManager::generatePassword
		 * @see \OCA\Preferred_Providers\Controller\PasswordController::generateAppPassword
		 * @see \OCA\GlobalSiteSelector\TokenHandler::generateAppPassword
		 *
		 * Token length: 22-256 - https://www.php.net/manual/en/session.configuration.php#ini.session.sid-length
		 * @see \OC\User\Session::createSessionToken
		 *
		 * Token length: 29
		 * @see \OCA\Settings\Controller\AuthSettingsController::generateRandomDeviceToken
		 * @see \OCA\Registration\Service\RegistrationService::generateAppPassword
		 */
		$tokenIdLength = strlen($tokenId);
		if ($tokenIdLength < self::TOKEN_MIN_LENGTH) {
			throw new InvalidTokenException('TokenId is too short for a generated token, minimum of '
												   . self::TOKEN_MIN_LENGTH
												   . ' characters is required, '
												   . $tokenIdLength
												   . ' characters given (should be a password during basic auth)'
			);
			// TODO: should we log this also? (i.e. if it's always caught elsewhere)
		}

		// Hash is needed for retrieval/cache management
		$tokenHash = $this->hashToken($tokenId);

		// Retrieve from local/memory cache if possible
		$token = $this->getTokenFromCache($tokenHash);

		// Retrieve from db if necessary
		if (!$token) {
			try {
				$token = $this->mapper->getToken($tokenHash);
				// Add the new token to the cache
				$this->cacheToken($token);
			} catch (DoesNotExistException $ex) {
				try {
					// Fallback for empty secret scenarios
					$token = $this->mapper->getToken($this->hashTokenWithEmptySecret($tokenId));
					// Rotate token if fallback succeeds
					$this->rotate($token, $tokenId, $tokenId);
				} catch (DoesNotExistException) {
					// Cache that the token doesn't exist
					$this->cacheInvalidHash($tokenHash);
					// Give up
					throw new InvalidTokenException('Token does not exist: ' . $ex->getMessage(), 0, $ex);
				}
			}
		}

		// If we make it this far, we found a token.

		// Check for token for expiration, wipe state, or an expired password (throws appropriately if so)
		$this->checkToken($token);

		// We're done
		return $token;
	}

	/**
	 * @throws InvalidTokenException when token doesn't exist
	 */
	private function getTokenFromCache(string $tokenHash): ?PublicKeyToken {
		$serializedToken = $this->cache->get($tokenHash);
		if ($serializedToken === false) {
			return null;
		}

		if ($serializedToken === null) {
			return null;
		}

		$token = unserialize($serializedToken, [
			'allowed_classes' => [PublicKeyToken::class],
		]);

		return $token instanceof PublicKeyToken ? $token : null;
	}

	private function cacheToken(PublicKeyToken $token): void {
		$this->cache->set($token->getToken(), serialize($token), self::TOKEN_CACHE_TTL);
	}

	private function cacheInvalidHash(string $tokenHash): void {
		// Invalid entries can be kept longer in cache since it’s unlikely to reuse them
		$this->cache->set($tokenHash, false, self::TOKEN_CACHE_TTL * 2);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTokenById(int $tokenId): OCPIToken {
		try {
			$token = $this->mapper->getTokenById($tokenId);
		} catch (DoesNotExistException $ex) {
			throw new InvalidTokenException("Token with ID $tokenId does not exist: " . $ex->getMessage(), 0, $ex);
		}

		$this->checkToken($token);

		return $token;
	}

	private function checkToken(PublicKeyToken $token): void {
		if ((int)$token->getExpires() !== 0 && $token->getExpires() < $this->time->getTime()) {
			throw new ExpiredTokenException($token);
		}

		if ($token->getType() === OCPIToken::WIPE_TOKEN) {
			throw new WipeTokenException($token);
		}

		if ($token->getPasswordInvalid() === true) {
			//The password is invalid we should throw an TokenPasswordExpiredException
			throw new TokenPasswordExpiredException($token);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function renewSessionToken(string $oldSessionId, string $sessionId): OCPIToken {
		return $this->atomic(function () use ($oldSessionId, $sessionId) {
			$token = $this->getToken($oldSessionId);

			if (!($token instanceof PublicKeyToken)) {
				throw new InvalidTokenException('Invalid token type');
			}

			$password = null;
			if (!is_null($token->getPassword())) {
				$privateKey = $this->decrypt($token->getPrivateKey(), $oldSessionId);
				$password = $this->decryptPassword($token->getPassword(), $privateKey);
			}

			$scope = $token->getScope() === '' ? null : $token->getScopeAsArray();
			$newToken = $this->generateToken(
				$sessionId,
				$token->getUID(),
				$token->getLoginName(),
				$password,
				$token->getName(),
				OCPIToken::TEMPORARY_TOKEN,
				$token->getRemember(),
				$scope,
			);
			$this->cacheToken($newToken);

			$this->cacheInvalidHash($token->getToken());
			$this->mapper->delete($token);

			return $newToken;
		}, $this->db);
	}

	/**
	 * {@inheritDoc}
	 */
	public function invalidateToken(string $token) {
		$tokenHash = $this->hashToken($token);
		$this->mapper->invalidate($this->hashToken($token));
		$this->mapper->invalidate($this->hashTokenWithEmptySecret($token));
		$this->cacheInvalidHash($tokenHash);
	}

	/**
	 * {@inheritDoc}
	 */
	public function invalidateTokenById(string $uid, int $id) {
		$token = $this->mapper->getTokenById($id);
		if ($token->getUID() !== $uid) {
			return;
		}
		$this->mapper->invalidate($token->getToken());
		$this->cacheInvalidHash($token->getToken());
	}

	/**
	 * {@inheritDoc}
	 */
	public function invalidateOldTokens() {
		$olderThan = $this->time->getTime() - $this->config->getSystemValueInt('session_lifetime', 60 * 60 * 24);
		$this->logger->debug('Invalidating session tokens older than ' . date('c', $olderThan), ['app' => 'cron']);
		$this->mapper->invalidateOld($olderThan, OCPIToken::TEMPORARY_TOKEN, OCPIToken::DO_NOT_REMEMBER);

		$rememberThreshold = $this->time->getTime() - $this->config->getSystemValueInt('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$this->logger->debug('Invalidating remembered session tokens older than ' . date('c', $rememberThreshold), ['app' => 'cron']);
		$this->mapper->invalidateOld($rememberThreshold, OCPIToken::TEMPORARY_TOKEN, OCPIToken::REMEMBER);

		$wipeThreshold = $this->time->getTime() - $this->config->getSystemValueInt('token_auth_wipe_token_retention', 60 * 60 * 24 * 60);
		$this->logger->debug('Invalidating auth tokens marked for remote wipe older than ' . date('c', $wipeThreshold), ['app' => 'cron']);
		$this->mapper->invalidateOld($wipeThreshold, OCPIToken::WIPE_TOKEN);

		$authTokenThreshold = $this->time->getTime() - $this->config->getSystemValueInt('token_auth_token_retention', 60 * 60 * 24 * 365);
		$this->logger->debug('Invalidating auth tokens older than ' . date('c', $authTokenThreshold), ['app' => 'cron']);
		$this->mapper->invalidateOld($authTokenThreshold, OCPIToken::PERMANENT_TOKEN);
	}

	/**
	 * {@inheritDoc}
	 */
	public function invalidateLastUsedBefore(string $uid, int $before): void {
		$this->mapper->invalidateLastUsedBefore($uid, $before);
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateToken(OCPIToken $token) {
		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}
		$this->mapper->update($token);
		$this->cacheToken($token);
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateTokenActivity(OCPIToken $token) {
		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}

		$activityInterval = $this->config->getSystemValueInt('token_auth_activity_update', 60);
		$activityInterval = min(max($activityInterval, 0), 300);

		/** @var PublicKeyToken $token */
		$now = $this->time->getTime();
		if ($token->getLastActivity() < ($now - $activityInterval)) {
			$token->setLastActivity($now);
			$this->mapper->updateActivity($token, $now);
			$this->cacheToken($token);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTokenByUser(string $uid): array {
		return $this->mapper->getTokenByUser($uid);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPassword(OCPIToken $savedToken, string $tokenId): string {
		if (!($savedToken instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}

		if ($savedToken->getPassword() === null) {
			throw new PasswordlessTokenException();
		}

		// Decrypt private key with tokenId
		$privateKey = $this->decrypt($savedToken->getPrivateKey(), $tokenId);

		// Decrypt password with private key
		return $this->decryptPassword($savedToken->getPassword(), $privateKey);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setPassword(OCPIToken $token, string $tokenId, string $password) {
		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}

		$this->atomic(function () use ($password, $token) {
			// When changing passwords all temp tokens are deleted
			$this->mapper->deleteTempToken($token);

			// Update the password for all tokens
			$tokens = $this->mapper->getTokenByUser($token->getUID());
			$hashedPassword = $this->hashPassword($password);
			foreach ($tokens as $t) {
				$publicKey = $t->getPublicKey();
				$t->setPassword($this->encryptPassword($password, $publicKey));
				$t->setPasswordHash($hashedPassword);
				$this->updateToken($t);
			}
		}, $this->db);
	}

	private function hashPassword(string $password): string {
		return $this->hasher->hash(sha1($password) . $password);
	}

	/**
	 * {@inheritDoc}
	 */
	public function rotate(OCPIToken $token, string $oldTokenId, string $newTokenId): OCPIToken {
		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}

		// Decrypt private key with oldTokenId
		$privateKey = $this->decrypt($token->getPrivateKey(), $oldTokenId);
		// Encrypt with the new token
		$token->setPrivateKey($this->encrypt($privateKey, $newTokenId));

		$token->setToken($this->hashToken($newTokenId));
		$this->updateToken($token);

		return $token;
	}

	private function encrypt(string $plaintext, string $token): string {
		$secret = $this->config->getSystemValueString('secret');
		return $this->crypto->encrypt($plaintext, $token . $secret);
	}

	/**
	 * @throws InvalidTokenException
	 */
	private function decrypt(string $cipherText, string $token): string {
		$secret = $this->config->getSystemValueString('secret');
		try {
			return $this->crypto->decrypt($cipherText, $token . $secret);
		} catch (\Exception $ex) {
			// Retry with empty secret as a fallback for instances where the secret might not have been set by accident
			try {
				return $this->crypto->decrypt($cipherText, $token);
			} catch (\Exception $ex2) {
				// Delete the invalid token
				$this->invalidateToken($token);
				throw new InvalidTokenException('Could not decrypt token password: ' . $ex->getMessage(), 0, $ex2);
			}
		}
	}

	private function encryptPassword(string $password, string $publicKey): string {
		openssl_public_encrypt($password, $encryptedPassword, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
		$encryptedPassword = base64_encode($encryptedPassword);

		return $encryptedPassword;
	}

	private function decryptPassword(string $encryptedPassword, string $privateKey): string {
		$encryptedPassword = base64_decode($encryptedPassword);
		openssl_private_decrypt($encryptedPassword, $password, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);

		return $password;
	}

	private function hashToken(string $token): string {
		$secret = $this->config->getSystemValueString('secret');
		return hash('sha512', $token . $secret);
	}

	/**
	 * @deprecated 26.0.0 Fallback for instances where the secret might not have been set by accident
	 */
	private function hashTokenWithEmptySecret(string $token): string {
		return hash('sha512', $token);
	}

	/**
	 * Generates a new token (leaving caller to handle persisting it)
	 *
	 * @return PublicKeyToken
	 * @throws \RuntimeException when OpenSSL reports a problem
	 */
	private function newToken(
		string $token,
		string $uid,
		string $loginName,
		?string $password,
		string $name,
		int $type,
		int $remember,
	): PublicKeyToken {

		$storeCrypted = $password !== null
			&& $this->config->getSystemValueBool('auth.storeCryptedPassword', true);

		// Enforce the maximum password length unless crypted passwords are disabled (or there's no p/w)
		if ($storeCrypted && strlen($password) > IUserManager::MAX_PASSWORD_LENGTH) {
			throw new \RuntimeException(
				'Passwords with more than 469 characters are not supported unless auth.storeCryptedPassword is disabled in config.php'
			);
		}

		// Create a a new token
		$dbToken = new PublicKeyToken();

		// Set the Uid and Login name for the new token
		$dbToken->setUid($uid);
		$dbToken->setLoginName($loginName);

		// Handle long passwords (but still <469) that require a larger key size
		$keySize = $storeCrypted
			&& strlen($password) > 250 ? 4096 : 2048; // Bug: Should probably be 214 not 250 given usage of RSAES-OAEP with SHA1

		// IDEA: Consider replacing some of this with sodium_crypto_box_seal / sodium_crypto_box_seal_open

		// Generate new private/public key pair (returned here as PEM encoded strings)
		[$publicKey, $privateKey] = $this->getKeyPair(['private_key_bits' => $keySize]);

		// Encrypt the private key
		$privateKey = $this->encrypt($privateKey, $token);

		// Set the public and private[encrypted] keys for the new token
		$dbToken->setPublicKey($publicKey);
		$dbToken->setPrivateKey($privateKey);

		// Set the password and p/w hash for the new token if we're using crypted passwords (which is typically the case)
		if ($storeCrypted) {
			$dbToken->setPassword($this->encryptPassword($password, $publicKey));
			$dbToken->setPasswordHash($this->hashPassword($password));
		}

		// Set various initial values for the new token
		$dbToken->setName($name);
		$dbToken->setToken($this->hashToken($token));
		$dbToken->setType($type);
		$dbToken->setRemember($remember);
		$dbToken->setLastActivity($this->time->getTime());
		$dbToken->setLastCheck($this->time->getTime());
		$dbToken->setVersion(PublicKeyToken::VERSION);

		// We're done
		return $dbToken;
	}

	// TODO: This method is duplicated in LoginFlowV2Service in the codebase; consolidate?
	private function getKeyPair(?array $customOptions = []): array {
		// Default options
		$defaultOptions = [
			'digest_alg' => 'sha512',
			'private_key_bits' => 2048,
		];

		// System (operator) options
		$systemOptions = $this->config->getSystemValue('openssl', []);

		// Merge all the specified options
		$options = array_merge(
			$defaultOptions, // lowest priority: defaults
			$customOptions, // medium priority (i.e. most of the time): custom config provided by caller
			$systemOptions // highest priority (rare): provided by operator via config.php
		);

		// Generate new private key
		$privateKey = openssl_pkey_new($options); /* @object OpenSSLAsymmetricKey */
		if ($privateKey === false) {
			$this->logOpensslError();
			throw new \RuntimeException('Could not generate new private key');
		}

		// Export private key as a PEM encoded string
		if (openssl_pkey_export($privateKey, $privateKeyPEM, null, $options) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		// Extract the public key as a PEM encoded string
		$publicKeyPEM = openssl_pkey_get_details($privateKey)['key'];

		return [$publicKeyPEM, $privateKeyPEM];
	}

	/**
	 * {@inheritDoc}
	 */
	public function markPasswordInvalid(OCPIToken $token, string $tokenId) {
		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException('Invalid token type');
		}

		$token->setPasswordInvalid(true);
		$this->mapper->update($token);
		$this->cacheToken($token);
	}

	/**
	 * {@inheritDoc}
	 */
	public function updatePasswords(string $uid, string $password) {
		// prevent setting an empty pw as result of pw-less-login
		if ($password === '' || !$this->config->getSystemValueBool('auth.storeCryptedPassword', true)) {
			return;
		}

		$this->atomic(function () use ($password, $uid) {
			// Update the password for all tokens
			$tokens = $this->mapper->getTokenByUser($uid);
			$newPasswordHash = null;

			/**
			 * - true: The password hash could not be verified anymore
			 *     and the token needs to be updated with the newly encrypted password
			 * - false: The hash could still be verified
			 * - missing: The hash needs to be verified
			 */
			$hashNeedsUpdate = [];

			foreach ($tokens as $t) {
				if (!isset($hashNeedsUpdate[$t->getPasswordHash()])) {
					if ($t->getPasswordHash() === null) {
						$hashNeedsUpdate[$t->getPasswordHash() ?: ''] = true;
					} elseif (!$this->hasher->verify(sha1($password) . $password, $t->getPasswordHash())) {
						$hashNeedsUpdate[$t->getPasswordHash() ?: ''] = true;
					} else {
						$hashNeedsUpdate[$t->getPasswordHash() ?: ''] = false;
					}
				}
				$needsUpdating = $hashNeedsUpdate[$t->getPasswordHash() ?: ''] ?? true;

				if ($needsUpdating) {
					if ($newPasswordHash === null) {
						$newPasswordHash = $this->hashPassword($password);
					}

					$publicKey = $t->getPublicKey();
					$t->setPassword($this->encryptPassword($password, $publicKey));
					$t->setPasswordHash($newPasswordHash);
					$t->setPasswordInvalid(false);
					$this->updateToken($t);
				}
			}

			// If password hashes are different we update them all to be equal so
			// that the next execution only needs to verify once
			if (count($hashNeedsUpdate) > 1) {
				$newPasswordHash = $this->hashPassword($password); // TODO (perf): Do we really need to compute this again? Isn't this already set above if needed?
				$this->mapper->updateHashesForUser($uid, $newPasswordHash);
			}
		}, $this->db);
	}

	// TODO: This method is duplicated in three places in the codebase; consolidate?
	private function logOpensslError() {
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something seems wrong with your OpenSSL setup: ' . implode(', ', $errors));
	}
}
