<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Authentication\Token;

use OC\Authentication\Exceptions\ExpiredTokenException;
use OC\Authentication\Exceptions\InvalidTokenException;
use OC\Authentication\Exceptions\TokenPasswordExpiredException;
use OC\Authentication\Exceptions\PasswordlessTokenException;
use OC\Authentication\Exceptions\WipeTokenException;
use OCP\AppFramework\Db\TTransactional;
use OCP\Cache\CappedMemoryCache;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\Security\ICrypto;
use OCP\Security\IHasher;
use Psr\Log\LoggerInterface;

class PublicKeyTokenProvider implements IProvider {
	public const TOKEN_MIN_LENGTH = 22;

	use TTransactional;

	/** @var PublicKeyTokenMapper */
	private $mapper;

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	private IDBConnection $db;

	/** @var LoggerInterface */
	private $logger;

	/** @var ITimeFactory */
	private $time;

	/** @var CappedMemoryCache */
	private $cache;

	private IHasher $hasher;

	public function __construct(PublicKeyTokenMapper $mapper,
								ICrypto $crypto,
								IConfig $config,
								IDBConnection $db,
								LoggerInterface $logger,
								ITimeFactory $time,
								IHasher $hasher) {
		$this->mapper = $mapper;
		$this->crypto = $crypto;
		$this->config = $config;
		$this->db = $db;
		$this->logger = $logger;
		$this->time = $time;

		$this->cache = new CappedMemoryCache();
		$this->hasher = $hasher;
	}

	/**
	 * {@inheritDoc}
	 */
	public function generateToken(string $token,
								  string $uid,
								  string $loginName,
								  ?string $password,
								  string $name,
								  int $type = IToken::TEMPORARY_TOKEN,
								  int $remember = IToken::DO_NOT_REMEMBER): IToken {
		if (strlen($token) < self::TOKEN_MIN_LENGTH) {
			$exception = new InvalidTokenException('Token is too short, minimum of ' . self::TOKEN_MIN_LENGTH . ' characters is required, ' . strlen($token) . ' characters given');
			$this->logger->error('Invalid token provided when generating new token', ['exception' => $exception]);
			throw $exception;
		}

		if (mb_strlen($name) > 128) {
			$name = mb_substr($name, 0, 120) . '…';
		}

		// We need to check against one old token to see if there is a password
		// hash that we can reuse for detecting outdated passwords
		$randomOldToken = $this->mapper->getFirstTokenForUser($uid);
		$oldTokenMatches = $randomOldToken && $randomOldToken->getPasswordHash() && $password !== null && $this->hasher->verify(sha1($password) . $password, $randomOldToken->getPasswordHash());

		$dbToken = $this->newToken($token, $uid, $loginName, $password, $name, $type, $remember);

		if ($oldTokenMatches) {
			$dbToken->setPasswordHash($randomOldToken->getPasswordHash());
		}

		$this->mapper->insert($dbToken);

		if (!$oldTokenMatches && $password !== null) {
			$this->updatePasswords($uid, $password);
		}

		// Add the token to the cache
		$this->cache[$dbToken->getToken()] = $dbToken;

		return $dbToken;
	}

	public function getToken(string $tokenId): IToken {
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
		if (strlen($tokenId) < self::TOKEN_MIN_LENGTH) {
			throw new InvalidTokenException('Token is too short for a generated token, should be the password during basic auth');
		}

		$tokenHash = $this->hashToken($tokenId);

		if (isset($this->cache[$tokenHash])) {
			if ($this->cache[$tokenHash] instanceof DoesNotExistException) {
				$ex = $this->cache[$tokenHash];
				throw new InvalidTokenException("Token does not exist: " . $ex->getMessage(), 0, $ex);
			}
			$token = $this->cache[$tokenHash];
		} else {
			try {
				$token = $this->mapper->getToken($tokenHash);
				$this->cache[$token->getToken()] = $token;
			} catch (DoesNotExistException $ex) {
				try {
					$token = $this->mapper->getToken($this->hashTokenWithEmptySecret($tokenId));
					$this->cache[$token->getToken()] = $token;
					$this->rotate($token, $tokenId, $tokenId);
				} catch (DoesNotExistException $ex2) {
					$this->cache[$tokenHash] = $ex2;
					throw new InvalidTokenException("Token does not exist: " . $ex->getMessage(), 0, $ex);
				}
			}
		}

		if ((int)$token->getExpires() !== 0 && $token->getExpires() < $this->time->getTime()) {
			throw new ExpiredTokenException($token);
		}

		if ($token->getType() === IToken::WIPE_TOKEN) {
			throw new WipeTokenException($token);
		}

		if ($token->getPasswordInvalid() === true) {
			//The password is invalid we should throw an TokenPasswordExpiredException
			throw new TokenPasswordExpiredException($token);
		}

		return $token;
	}

	public function getTokenById(int $tokenId): IToken {
		try {
			$token = $this->mapper->getTokenById($tokenId);
		} catch (DoesNotExistException $ex) {
			throw new InvalidTokenException("Token with ID $tokenId does not exist: " . $ex->getMessage(), 0, $ex);
		}

		if ((int)$token->getExpires() !== 0 && $token->getExpires() < $this->time->getTime()) {
			throw new ExpiredTokenException($token);
		}

		if ($token->getType() === IToken::WIPE_TOKEN) {
			throw new WipeTokenException($token);
		}

		if ($token->getPasswordInvalid() === true) {
			//The password is invalid we should throw an TokenPasswordExpiredException
			throw new TokenPasswordExpiredException($token);
		}

		return $token;
	}

	public function renewSessionToken(string $oldSessionId, string $sessionId): IToken {
		$this->cache->clear();

		return $this->atomic(function () use ($oldSessionId, $sessionId) {
			$token = $this->getToken($oldSessionId);

			if (!($token instanceof PublicKeyToken)) {
				throw new InvalidTokenException("Invalid token type");
			}

			$password = null;
			if (!is_null($token->getPassword())) {
				$privateKey = $this->decrypt($token->getPrivateKey(), $oldSessionId);
				$password = $this->decryptPassword($token->getPassword(), $privateKey);
			}
			$newToken = $this->generateToken(
				$sessionId,
				$token->getUID(),
				$token->getLoginName(),
				$password,
				$token->getName(),
				IToken::TEMPORARY_TOKEN,
				$token->getRemember()
			);

			$this->mapper->delete($token);

			return $newToken;
		}, $this->db);
	}

	public function invalidateToken(string $token) {
		$this->cache->clear();

		$this->mapper->invalidate($this->hashToken($token));
		$this->mapper->invalidate($this->hashTokenWithEmptySecret($token));
	}

	public function invalidateTokenById(string $uid, int $id) {
		$this->cache->clear();

		$this->mapper->deleteById($uid, $id);
	}

	public function invalidateOldTokens() {
		$this->cache->clear();

		$olderThan = $this->time->getTime() - (int) $this->config->getSystemValue('session_lifetime', 60 * 60 * 24);
		$this->logger->debug('Invalidating session tokens older than ' . date('c', $olderThan), ['app' => 'cron']);
		$this->mapper->invalidateOld($olderThan, IToken::DO_NOT_REMEMBER);
		$rememberThreshold = $this->time->getTime() - (int) $this->config->getSystemValue('remember_login_cookie_lifetime', 60 * 60 * 24 * 15);
		$this->logger->debug('Invalidating remembered session tokens older than ' . date('c', $rememberThreshold), ['app' => 'cron']);
		$this->mapper->invalidateOld($rememberThreshold, IToken::REMEMBER);
	}

	public function updateToken(IToken $token) {
		$this->cache->clear();

		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
		}
		$this->mapper->update($token);
	}

	public function updateTokenActivity(IToken $token) {
		$this->cache->clear();

		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
		}

		$activityInterval = $this->config->getSystemValueInt('token_auth_activity_update', 60);
		$activityInterval = min(max($activityInterval, 0), 300);

		/** @var PublicKeyToken $token */
		$now = $this->time->getTime();
		if ($token->getLastActivity() < ($now - $activityInterval)) {
			$token->setLastActivity($now);
			$this->mapper->updateActivity($token, $now);
		}
	}

	public function getTokenByUser(string $uid): array {
		return $this->mapper->getTokenByUser($uid);
	}

	public function getPassword(IToken $savedToken, string $tokenId): string {
		if (!($savedToken instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
		}

		if ($savedToken->getPassword() === null) {
			throw new PasswordlessTokenException();
		}

		// Decrypt private key with tokenId
		$privateKey = $this->decrypt($savedToken->getPrivateKey(), $tokenId);

		// Decrypt password with private key
		return $this->decryptPassword($savedToken->getPassword(), $privateKey);
	}

	public function setPassword(IToken $token, string $tokenId, string $password) {
		$this->cache->clear();

		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
		}

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
	}

	private function hashPassword(string $password): string {
		return $this->hasher->hash(sha1($password) . $password);
	}

	public function rotate(IToken $token, string $oldTokenId, string $newTokenId): IToken {
		$this->cache->clear();

		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
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
		$secret = $this->config->getSystemValue('secret');
		return $this->crypto->encrypt($plaintext, $token . $secret);
	}

	/**
	 * @throws InvalidTokenException
	 */
	private function decrypt(string $cipherText, string $token): string {
		$secret = $this->config->getSystemValue('secret');
		try {
			return $this->crypto->decrypt($cipherText, $token . $secret);
		} catch (\Exception $ex) {
			// Retry with empty secret as a fallback for instances where the secret might not have been set by accident
			try {
				return $this->crypto->decrypt($cipherText, $token);
			} catch (\Exception $ex2) {
				// Delete the invalid token
				$this->invalidateToken($token);
				throw new InvalidTokenException("Could not decrypt token password: " . $ex->getMessage(), 0, $ex2);
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
		$secret = $this->config->getSystemValue('secret');
		return hash('sha512', $token . $secret);
	}

	/**
	 * @deprecated Fallback for instances where the secret might not have been set by accident
	 */
	private function hashTokenWithEmptySecret(string $token): string {
		return hash('sha512', $token);
	}

	/**
	 * @throws \RuntimeException when OpenSSL reports a problem
	 */
	private function newToken(string $token,
							  string $uid,
							  string $loginName,
							  $password,
							  string $name,
							  int $type,
							  int $remember): PublicKeyToken {
		$dbToken = new PublicKeyToken();
		$dbToken->setUid($uid);
		$dbToken->setLoginName($loginName);

		$config = array_merge([
			'digest_alg' => 'sha512',
			'private_key_bits' => $password !== null && strlen($password) > 250 ? 4096 : 2048,
		], $this->config->getSystemValue('openssl', []));

		// Generate new key
		$res = openssl_pkey_new($config);
		if ($res === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		if (openssl_pkey_export($res, $privateKey, null, $config) === false) {
			$this->logOpensslError();
			throw new \RuntimeException('OpenSSL reported a problem');
		}

		// Extract the public key from $res to $pubKey
		$publicKey = openssl_pkey_get_details($res);
		$publicKey = $publicKey['key'];

		$dbToken->setPublicKey($publicKey);
		$dbToken->setPrivateKey($this->encrypt($privateKey, $token));

		if (!is_null($password) && $this->config->getSystemValueBool('auth.storeCryptedPassword', true)) {
			if (strlen($password) > IUserManager::MAX_PASSWORD_LENGTH) {
				throw new \RuntimeException('Trying to save a password with more than 469 characters is not supported. If you want to use big passwords, disable the auth.storeCryptedPassword option in config.php');
			}
			$dbToken->setPassword($this->encryptPassword($password, $publicKey));
			$dbToken->setPasswordHash($this->hashPassword($password));
		}

		$dbToken->setName($name);
		$dbToken->setToken($this->hashToken($token));
		$dbToken->setType($type);
		$dbToken->setRemember($remember);
		$dbToken->setLastActivity($this->time->getTime());
		$dbToken->setLastCheck($this->time->getTime());
		$dbToken->setVersion(PublicKeyToken::VERSION);

		return $dbToken;
	}

	public function markPasswordInvalid(IToken $token, string $tokenId) {
		$this->cache->clear();

		if (!($token instanceof PublicKeyToken)) {
			throw new InvalidTokenException("Invalid token type");
		}

		$token->setPasswordInvalid(true);
		$this->mapper->update($token);
	}

	public function updatePasswords(string $uid, string $password) {
		$this->cache->clear();

		// prevent setting an empty pw as result of pw-less-login
		if ($password === '' || !$this->config->getSystemValueBool('auth.storeCryptedPassword', true)) {
			return;
		}

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
			$newPasswordHash = $this->hashPassword($password);
			$this->mapper->updateHashesForUser($uid, $newPasswordHash);
		}
	}

	private function logOpensslError() {
		$errors = [];
		while ($error = openssl_error_string()) {
			$errors[] = $error;
		}
		$this->logger->critical('Something is wrong with your openssl setup: ' . implode(', ', $errors));
	}
}
