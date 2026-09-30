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
	private array $passwordResetUsers = [];

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
			$this->passwordResetUsers[$uid] = true;
		} else {
			unset($this->passwordResetUsers[$uid]);
		}
	}

	/**
	 * Update encryption keys after a user's account password changes.
	 *
	 * For the current user, re-encrypts the existing private key with the new
	 * password. For another user, may create a new keypair and recover file-key
	 * access when recovery is enabled and a recovery password is provided.
	 *
	 * @param string $userId User whose password changed
	 * @param string $password New account password
	 * @param string|null $recoveryPassword Recovery password, when needed to recover file-key access
	 *
	 * @return bool True if key handling was intentionally skipped for a password
	 *              reset or master-key mode, or the key-update path completed
	 *              without a detected failure. False if the user is unknown, no
	 *              safe key update was made, or an operation failed. Callers
	 *              should determine the follow-up from the specific outcome. If
	 *              the account password changed but the private key remains
	 *              protected by the old password, direct the user to update it
	 *              in Encryption settings; if they do not know that password,
	 *              administrator recovery may be needed if configured.
	 *
	 * @throws GenericEncryptionException If requested recovery cannot decrypt the recovery private key
	 *
	 * @todo A true result does not presently guarantee that every file key was recovered.
	 * @todo Replace the boolean with a result type so callers can distinguish
	 *       successful updates, intentional skips, required user action, and failures.
	 */
	public function setPassphraseForUser(string $userId, string $password, ?string $recoveryPassword = null): bool {
		// A password-reset flow handles encryption keys separately.
		if (isset($this->passwordResetUsers[$userId])) {
			return true;
		}

		// With a master key, the user's password does not protect the encryption keypair.
		if ($this->util->isMasterKeyEnabled()) {
			$this->logger->error('setPassphraseForUser should never be called when master key is enabled');
			return true;
		}

		// Do not attempt key updates for an unknown user.
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return false;
		}

		// A logged-in user changing their own password can keep their existing keypair.
		// The existing private key will merely be re-encrypted with the new account password. 
		$currentUser = $this->userSession->getUser();
		if ($currentUser !== null && $userId === $currentUser->getUID()) {
			$privateKey = $this->session->getPrivateKey();

			// Re-encrypt the existing private key with the new account password.
			$encryptedPrivateKey = $this->crypt->encryptPrivateKey($privateKey, $password, $userId);

			if ($encryptedPrivateKey === false) {
				$this->logger->error('Encryption could not update users encryption password');
				return false;
			}
			
			$key = $this->crypt->generateHeader() . $encryptedPrivateKey;

			if (!$this->keyManager->setPrivateKey($userId, $key)) {
				$this->logger->error('Encryption could not save the users private key');
				return false;
			}

			// Session does not need to be updated since the private key has not changed,
			// only the passphrase used to decrypt it has changed.
			return true;
		}
		
		// When changing another user's password, the existing private key is not available here.
		// Generating a new keypair is an option, but only if recovery is enabled for that user
		// (and a non-empty recovery password is provided), the user has no encryption keys, or
		// the user has no files.
		$recoveryPassword = $recoveryPassword ?? '';
		$this->initMountPoints($user);

		$hasKeys = $this->keyManager->userHasKeys($userId);
		$hasFiles = $this->util->userHasFiles($userId);
		$recoveryEnabled = $this->recovery->isRecoveryEnabledForUser($userId);

		$needsRecovery = $recoveryEnabled && $recoveryPassword !== '' && $hasFiles;

		// TODO: Distinguish first-time encryption (no existing encrypted data)
		// from a missing keypair for a user whose files directory already
		// exists. Rotating keys in the latter case without recovery may make
		// existing encrypted file keys inaccessible. This preserves the
		// existing behavior for now.
		$shouldRotateKeys = $needsRecovery || !$hasKeys || !$hasFiles;

		// Nothing we can do (that wouldn't be destructive).
		if (!$shouldRotateKeys) {
			return false;
		}

		if ($needsRecovery) {
			// Validate recovery credentials before modifying the user's keypair.
			// Treat either a false result or a decryption exception as failure.
			$recoveryKey = $this->keyManager->getSystemPrivateKey(
				$this->keyManager->getRecoveryKeyId()
			);
			try {
				$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey, $recoveryPassword);
			} catch (\Exception) {
				$decryptedRecoveryKey = false;
			}

			if ($decryptedRecoveryKey === false) {
				$message = 'Can not decrypt the recovery key. Maybe you provided the wrong password. Try again.';
				throw new GenericEncryptionException($message, $message);
			}
		}

		$keyPair = $this->crypt->createKeyPair();

		if ($keyPair === false) {
			$this->logger->error('Could not create new private key-pair for user.');
			return false;
		}

		// Encrypt new private key with new password.
		$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], $password, $userId);
		if ($encryptedKey === false) {
			$this->logger->error('Encryption could not update users encryption password');
			return false;
		}

		if (!$this->keyManager->setPublicKey($userId, $keyPair['publicKey'])) {
			$this->logger->error('Encryption could not save the users public key');
			return false;
		}

		if (!$this->keyManager->setPrivateKey($userId, $this->crypt->generateHeader() . $encryptedKey)) {
			$this->logger->error('Encryption could not save the users private key');
			return false;
		}

		if ($needsRecovery) {
			// Re-wrap this user's file keys for the new keypair. The
			// encrypted file contents themselves are not changed.
			$this->recovery->recoverUsersFiles($recoveryPassword, $userId);
		}

		return true;
	}

	/**
	 * Init mount points for given user
	 */
	private function initMountPoints(IUser $user): void {
		Filesystem::initMountPoints($user);
	}
}
