<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Encryption\Services;

use OC\Files\Filesystem;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Recovery;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

class PassphraseService {

	/** @var array<string, bool> */
	private static array $passwordResetUsers = [];

	public function __construct(
		private Util $util,
		private Crypt $crypt,
		private Session $session,
		private Recovery $recovery,
		private KeyManager $keyManager,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private IUserSession $userSession,
	) {
	}

	public function setProcessingReset(string $uid, bool $processing = true): void {
		if ($processing) {
			self::$passwordResetUsers[$uid] = true;
		} else {
			unset(self::$passwordResetUsers[$uid]);
		}
	}

	/**
	 * Change a user's encryption passphrase
	 */
	public function setPassphraseForUser(string $userId, string $password, ?string $recoveryPassword = null): bool {
		// if we are in the process to resetting a user password, we have nothing
		// to do here
		if (isset(self::$passwordResetUsers[$userId])) {
			return true;
		}

		if ($this->util->isMasterKeyEnabled()) {
			$this->logger->error('setPassphraseForUser should never be called when master key is enabled');
			return true;
		}

		// Check user exists on backend
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return false;
		}

		// Get existing decrypted private key
		$currentUser = $this->userSession->getUser();

		// current logged in user changes his own password
		if ($currentUser !== null && $userId === $currentUser->getUID()) {
			$privateKey = $this->session->getPrivateKey();

			// Encrypt private key with new user pwd as passphrase
			$encryptedPrivateKey = $this->crypt->encryptPrivateKey($privateKey, $password, $userId);

			// Save private key
			if ($encryptedPrivateKey !== false) {
				$key = $this->crypt->generateHeader() . $encryptedPrivateKey;
				$this->keyManager->setPrivateKey($userId, $key);
				return true;
			}

			$this->logger->error('Encryption could not update users encryption password');

			// NOTE: Session does not need to be updated as the
			// private key has not changed, only the passphrase
			// used to decrypt it has changed
		} else {
			// admin changed the password for a different user, create new keys and re-encrypt file keys
			$recoveryPassword = $recoveryPassword ?? '';
			$this->initMountPoints($user);

			$recoveryKeyId = $this->keyManager->getRecoveryKeyId();
			$recoveryKey = $this->keyManager->getSystemPrivateKey($recoveryKeyId);
			try {
				$this->crypt->decryptPrivateKey($recoveryKey, $recoveryPassword);
			} catch (\Exception) {
				$message = 'Can not decrypt the recovery key. Maybe you provided the wrong password. Try again.';
				throw new GenericEncryptionException($message, $message);
			}

			// we generate new keys if...
			// ...we have a recovery password and the user enabled the recovery key
			// ...encryption was activated for the first time (no keys exists)
			// ...the user doesn't have any files
			if (
				($this->recovery->isRecoveryEnabledForUser($userId) && $recoveryPassword !== '')
				|| !$this->keyManager->userHasKeys($userId)
				|| !$this->util->userHasFiles($userId)
			) {
				$keyPair = $this->crypt->createKeyPair();
				if ($keyPair === false) {
					$this->logger->error('Could not create new private key-pair for user.');
					return false;
				}

				// Save public key
				$this->keyManager->setPublicKey($userId, $keyPair['publicKey']);

				// Encrypt private key with new password
				$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], $password, $userId);
				if ($encryptedKey === false) {
					$this->logger->error('Encryption could not update users encryption password');
					return false;
				}

				$this->keyManager->setPrivateKey($userId, $this->crypt->generateHeader() . $encryptedKey);

				if ($recoveryPassword !== '') {
					// if recovery key is set we can re-encrypt the key files
					$this->recovery->recoverUsersFiles($recoveryPassword, $userId);
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Init mount points for given user
	 */
	private function initMountPoints(IUser $user): void {
		Filesystem::initMountPoints($user);
	}
}
