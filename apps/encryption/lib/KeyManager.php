<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Encryption;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\Exceptions\PrivateKeyMissingException;
use OCA\Encryption\Exceptions\PublicKeyMissingException;
use OCP\Encryption\Keys\IStorage;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use Psr\Log\LoggerInterface;

class KeyManager {
	private string $recoveryKeyId;
	private string $publicShareKeyId;
	private string $masterKeyId;
	private string $keyId;
	private string $publicKeyId = 'publicKey';
	private string $privateKeyId = 'privateKey';
	private string $shareKeyId = 'shareKey';
	private string $fileKeyId = 'fileKey';

	public function __construct(
		private IStorage $keyStorage,
		private Crypt $crypt,
		private IConfig $config,
		IUserSession $userSession,
		private Session $session,
		private LoggerInterface $logger,
		private Util $util,
		private ILockingProvider $lockingProvider,
	) {
		$this->recoveryKeyId = $this->config->getAppValue('encryption',
			'recoveryKeyId');
		if (empty($this->recoveryKeyId)) {
			$this->recoveryKeyId = 'recoveryKey_' . substr(md5((string)time()), 0, 8);
			$this->config->setAppValue('encryption',
				'recoveryKeyId',
				$this->recoveryKeyId);
		}

		$this->publicShareKeyId = $this->config->getAppValue('encryption',
			'publicShareKeyId');
		if (empty($this->publicShareKeyId)) {
			$this->publicShareKeyId = 'pubShare_' . substr(md5((string)time()), 0, 8);
			$this->config->setAppValue('encryption', 'publicShareKeyId', $this->publicShareKeyId);
		}

		$this->masterKeyId = $this->config->getAppValue('encryption',
			'masterKeyId');
		if (empty($this->masterKeyId)) {
			$this->masterKeyId = 'master_' . substr(md5((string)time()), 0, 8);
			$this->config->setAppValue('encryption', 'masterKeyId', $this->masterKeyId);
		}

		$this->keyId = $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : false;
	}

	/**
	 * check if key pair for public link shares exists, if not we create one
	 */
	public function validateShareKey() {
		$shareKey = $this->getPublicShareKey();
		if (empty($shareKey)) {
			$this->lockingProvider->acquireLock('encryption-generateSharedKey', ILockingProvider::LOCK_EXCLUSIVE, 'Encryption: shared key generation');
			try {
				$keyPair = $this->crypt->createKeyPair();

				// Save public key
				$this->keyStorage->setSystemUserKey(
					$this->publicShareKeyId . '.' . $this->publicKeyId, $keyPair['publicKey'],
					Encryption::ID);

				// Encrypt private key empty passphrase
				$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], '');
				$header = $this->crypt->generateHeader();
				$this->setSystemPrivateKey($this->publicShareKeyId, $header . $encryptedKey);
			} catch (\Throwable $e) {
				$this->lockingProvider->releaseLock('encryption-generateSharedKey', ILockingProvider::LOCK_EXCLUSIVE);
				throw $e;
			}
			$this->lockingProvider->releaseLock('encryption-generateSharedKey', ILockingProvider::LOCK_EXCLUSIVE);
		}
	}

	/**
	 * check if a key pair for the master key exists, if not we create one
	 */
	public function validateMasterKey() {
		if ($this->util->isMasterKeyEnabled() === false) {
			return;
		}

		$publicMasterKey = $this->getPublicMasterKey();
		$privateMasterKey = $this->getPrivateMasterKey();

		if (empty($publicMasterKey) && empty($privateMasterKey)) {
			// There could be a race condition here if two requests would trigger
			// the generation the second one would enter the key generation as long
			// as the first one didn't write the key to the keystorage yet
			$this->lockingProvider->acquireLock('encryption-generateMasterKey', ILockingProvider::LOCK_EXCLUSIVE, 'Encryption: master key generation');
			try {
				$keyPair = $this->crypt->createKeyPair();

				// Save public key
				$this->keyStorage->setSystemUserKey(
					$this->masterKeyId . '.' . $this->publicKeyId, $keyPair['publicKey'],
					Encryption::ID);

				// Encrypt private key with system password
				$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], $this->getMasterKeyPassword(), $this->masterKeyId);
				$header = $this->crypt->generateHeader();
				$this->setSystemPrivateKey($this->masterKeyId, $header . $encryptedKey);
			} catch (\Throwable $e) {
				$this->lockingProvider->releaseLock('encryption-generateMasterKey', ILockingProvider::LOCK_EXCLUSIVE);
				throw $e;
			}
			$this->lockingProvider->releaseLock('encryption-generateMasterKey', ILockingProvider::LOCK_EXCLUSIVE);
		} elseif (empty($publicMasterKey)) {
			$this->logger->error('A private master key is available but the public key could not be found. This should never happen.');
			return;
		} elseif (empty($privateMasterKey)) {
			$this->logger->error('A public master key is available but the private key could not be found. This should never happen.');
			return;
		}

		if (!$this->session->isPrivateKeySet()) {
			$masterKey = $this->getSystemPrivateKey($this->masterKeyId);
			$decryptedMasterKey = $this->crypt->decryptPrivateKey($masterKey, $this->getMasterKeyPassword(), $this->masterKeyId);
			$this->session->setPrivateKey($decryptedMasterKey);
		}

		// after the encryption key is available we are ready to go
		$this->session->setStatus(Session::INIT_SUCCESSFUL);
	}

	/**
	 * @return bool
	 */
	public function recoveryKeyExists() {
		$key = $this->getRecoveryKey();
		return !empty($key);
	}

	/**
	 * get recovery key
	 *
	 * @return string
	 */
	public function getRecoveryKey() {
		return $this->keyStorage->getSystemUserKey($this->recoveryKeyId . '.' . $this->publicKeyId, Encryption::ID);
	}

	/**
	 * get recovery key ID
	 *
	 * @return string
	 */
	public function getRecoveryKeyId() {
		return $this->recoveryKeyId;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function checkRecoveryPassword($password) {
		$recoveryKey = $this->keyStorage->getSystemUserKey($this->recoveryKeyId . '.' . $this->privateKeyId, Encryption::ID);
		$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey, $password);

		if ($decryptedRecoveryKey) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @param array $keyPair
	 * @return bool
	 */
	public function storeKeyPair($uid, $password, $keyPair) {
		// Save Public Key
		$this->setPublicKey($uid, $keyPair['publicKey']);

		$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], $password, $uid);

		$header = $this->crypt->generateHeader();

		if ($encryptedKey) {
			$this->setPrivateKey($uid, $header . $encryptedKey);
			return true;
		}
		return false;
	}

	/**
	 * @param string $password
	 * @param array $keyPair
	 * @return bool
	 */
	public function setRecoveryKey($password, $keyPair) {
		// Save Public Key
		$this->keyStorage->setSystemUserKey($this->getRecoveryKeyId().
			'.' . $this->publicKeyId,
			$keyPair['publicKey'],
			Encryption::ID);

		$encryptedKey = $this->crypt->encryptPrivateKey($keyPair['privateKey'], $password);
		$header = $this->crypt->generateHeader();

		if ($encryptedKey) {
			$this->setSystemPrivateKey($this->getRecoveryKeyId(), $header . $encryptedKey);
			return true;
		}
		return false;
	}

	/**
	 * @param $userId
	 * @param $key
	 * @return bool
	 */
	public function setPublicKey($userId, $key) {
		return $this->keyStorage->setUserKey($userId, $this->publicKeyId, $key, Encryption::ID);
	}

	/**
	 * @param $userId
	 * @param string $key
	 * @return bool
	 */
	public function setPrivateKey($userId, $key) {
		return $this->keyStorage->setUserKey($userId,
			$this->privateKeyId,
			$key,
			Encryption::ID);
	}

	/**
	 * write file key to key storage
	 *
	 * @param string $path
	 * @param string $key
	 * @return boolean
	 */
	public function setFileKey($path, $key) {
		return $this->keyStorage->setFileKey($path, $this->fileKeyId, $key, Encryption::ID);
	}

	/**
	 * set all file keys (the file key and the corresponding share keys)
	 *
	 * @param string $path
	 * @param array $keys
	 */
	public function setAllFileKeys($path, $keys) {
		$this->setFileKey($path, $keys['data']);
		foreach ($keys['keys'] as $uid => $keyFile) {
			$this->setShareKey($path, $uid, $keyFile);
		}
	}

	/**
	 * write share key to the key storage
	 *
	 * @param string $path
	 * @param string $uid
	 * @param string $key
	 * @return boolean
	 */
	public function setShareKey($path, $uid, $key) {
		$keyId = $uid . '.' . $this->shareKeyId;
		return $this->keyStorage->setFileKey($path, $keyId, $key, Encryption::ID);
	}

	/**
	 * Decrypt private key and store it
	 *
	 * @param string $uid user id
	 * @param string $passPhrase users password
	 * @return boolean
	 */
	public function init($uid, $passPhrase) {
		$this->session->setStatus(Session::INIT_EXECUTED);

		try {
			if ($this->util->isMasterKeyEnabled()) {
				$uid = $this->getMasterKeyId();
				$passPhrase = $this->getMasterKeyPassword();
				$privateKey = $this->getSystemPrivateKey($uid);
			} else {
				$privateKey = $this->getPrivateKey($uid);
			}
			$privateKey = $this->crypt->decryptPrivateKey($privateKey, $passPhrase, $uid);
		} catch (PrivateKeyMissingException $e) {
			return false;
		} catch (DecryptionFailedException $e) {
			return false;
		} catch (\Exception $e) {
			$this->logger->warning(
				'Could not decrypt the private key from user "' . $uid . '"" during login. Assume password change on the user back-end.',
				[
					'app' => 'encryption',
					'exception' => $e,
				]
			);
			return false;
		}

		if ($privateKey) {
			$this->session->setPrivateKey($privateKey);
			$this->session->setStatus(Session::INIT_SUCCESSFUL);
			return true;
		}

		return false;
	}

	/**
	 * @param $userId
	 * @return string
	 * @throws PrivateKeyMissingException
	 */
	public function getPrivateKey($userId) {
		$privateKey = $this->keyStorage->getUserKey($userId,
			$this->privateKeyId, Encryption::ID);

		if (strlen($privateKey) !== 0) {
			return $privateKey;
		}
		throw new PrivateKeyMissingException($userId);
	}

	/**
	 * @param string $path
	 * @param $uid
	 * @param ?bool $useLegacyFileKey null means try both
	 * @return string
	 */
	public function getFileKey(string $path, ?string $uid, ?bool $useLegacyFileKey): string {
		if ($uid === '') {
			$uid = null;
		}
		$publicAccess = is_null($uid);
		$encryptedFileKey = '';
		if ($useLegacyFileKey ?? true) {
			$encryptedFileKey = $this->keyStorage->getFileKey($path, $this->fileKeyId, Encryption::ID);

			if (empty($encryptedFileKey) && $useLegacyFileKey) {
				return '';
			}
		}

		if ($this->util->isMasterKeyEnabled()) {
			$uid = $this->getMasterKeyId();
			$shareKey = $this->getShareKey($path, $uid);
			if ($publicAccess) {
				$privateKey = $this->getSystemPrivateKey($uid);
				$privateKey = $this->crypt->decryptPrivateKey($privateKey, $this->getMasterKeyPassword(), $uid);
			} else {
				// when logged in, the master key is already decrypted in the session
				$privateKey = $this->session->getPrivateKey();
			}
		} elseif ($publicAccess) {
			// use public share key for public links
			$uid = $this->getPublicShareKeyId();
			$shareKey = $this->getShareKey($path, $uid);
			$privateKey = $this->keyStorage->getSystemUserKey($this->publicShareKeyId . '.' . $this->privateKeyId, Encryption::ID);
			$privateKey = $this->crypt->decryptPrivateKey($privateKey);
		} else {
			$shareKey = $this->getShareKey($path, $uid);
			$privateKey = $this->session->getPrivateKey();
		}

		if ($useLegacyFileKey ?? true) {
			if ($encryptedFileKey && $shareKey && $privateKey) {
				return $this->crypt->multiKeyDecryptLegacy($encryptedFileKey,
					$shareKey,
					$privateKey);
			}
		}
		if (!($useLegacyFileKey ?? false)) {
			if ($shareKey && $privateKey) {
				return $this->crypt->multiKeyDecrypt($shareKey, $privateKey);
			}
		}

		return '';
	}

	/**
	 * Get the current version of a file
	 *
	 * @param string $path
	 * @param View $view
	 * @return int
	 */
	public function getVersion($path, View $view) {
		$fileInfo = $view->getFileInfo($path);
		if ($fileInfo === false) {
			return 0;
		}
		return $fileInfo->getEncryptedVersion();
	}

	/**
	 * Set the current version of a file
	 *
	 * @param string $path
	 * @param int $version
	 * @param View $view
	 */
	public function setVersion($path, $version, View $view) {
		$fileInfo = $view->getFileInfo($path);

		if ($fileInfo !== false) {
			$cache = $fileInfo->getStorage()->getCache();
			$cache->update($fileInfo->getId(), ['encrypted' => $version, 'encryptedVersion' => $version]);
		}
	}

	/**
	 * get the encrypted file key
	 *
	 * @param string $path
	 * @return string
	 */
	public function getEncryptedFileKey($path) {
		$encryptedFileKey = $this->keyStorage->getFileKey($path,
			$this->fileKeyId, Encryption::ID);

		return $encryptedFileKey;
	}

	/**
	 * delete share key
	 *
	 * @param string $path
	 * @param string $keyId
	 * @return boolean
	 */
	public function deleteShareKey($path, $keyId) {
		return $this->keyStorage->deleteFileKey(
			$path,
			$keyId . '.' . $this->shareKeyId,
			Encryption::ID);
	}


	/**
	 * @param $path
	 * @param $uid
	 * @return mixed
	 */
	public function getShareKey($path, $uid) {
		$keyId = $uid . '.' . $this->shareKeyId;
		return $this->keyStorage->getFileKey($path, $keyId, Encryption::ID);
	}

	/**
	 * check if user has a private and a public key
	 *
	 * @param string $userId
	 * @return bool
	 * @throws PrivateKeyMissingException
	 * @throws PublicKeyMissingException
	 */
	public function userHasKeys($userId) {
		$privateKey = $publicKey = true;
		$exception = null;

		try {
			$this->getPrivateKey($userId);
		} catch (PrivateKeyMissingException $e) {
			$privateKey = false;
			$exception = $e;
		}
		try {
			$this->getPublicKey($userId);
		} catch (PublicKeyMissingException $e) {
			$publicKey = false;
			$exception = $e;
		}

		if ($privateKey && $publicKey) {
			return true;
		} elseif (!$privateKey && !$publicKey) {
			return false;
		} else {
			throw $exception;
		}
	}

	/**
	 * @param $userId
	 * @return mixed
	 * @throws PublicKeyMissingException
	 */
	public function getPublicKey($userId) {
		$publicKey = $this->keyStorage->getUserKey($userId, $this->publicKeyId, Encryption::ID);

		if (strlen($publicKey) !== 0) {
			return $publicKey;
		}
		throw new PublicKeyMissingException($userId);
	}

	public function getPublicShareKeyId() {
		return $this->publicShareKeyId;
	}

	/**
	 * get public key for public link shares
	 *
	 * @return string
	 */
	public function getPublicShareKey() {
		return $this->keyStorage->getSystemUserKey($this->publicShareKeyId . '.' . $this->publicKeyId, Encryption::ID);
	}

	/**
	 * @param string $purpose
	 * @param string $uid
	 */
	public function backupUserKeys($purpose, $uid) {
		$this->keyStorage->backupUserKeys(Encryption::ID, $purpose, $uid);
	}

	/**
	 * create a backup of the users private and public key and then delete it
	 *
	 * @param string $uid
	 */
	public function deleteUserKeys($uid) {
		$this->deletePublicKey($uid);
		$this->deletePrivateKey($uid);
	}

	/**
	 * @param $uid
	 * @return bool
	 */
	public function deletePublicKey($uid) {
		return $this->keyStorage->deleteUserKey($uid, $this->publicKeyId, Encryption::ID);
	}

	/**
	 * @param string $uid
	 * @return bool
	 */
	private function deletePrivateKey($uid) {
		return $this->keyStorage->deleteUserKey($uid, $this->privateKeyId, Encryption::ID);
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function deleteAllFileKeys($path) {
		return $this->keyStorage->deleteAllFileKeys($path);
	}

	public function deleteLegacyFileKey(string $path): bool {
		return $this->keyStorage->deleteFileKey($path, $this->fileKeyId, Encryption::ID);
	}

	/**
	 * @param array $userIds
	 * @return array
	 * @throws PublicKeyMissingException
	 */
	public function getPublicKeys(array $userIds) {
		$keys = [];

		foreach ($userIds as $userId) {
			try {
				$keys[$userId] = $this->getPublicKey($userId);
			} catch (PublicKeyMissingException $e) {
				continue;
			}
		}

		return $keys;
	}

	/**
	 * @param string $keyId
	 * @return string returns openssl key
	 */
	public function getSystemPrivateKey($keyId) {
		return $this->keyStorage->getSystemUserKey($keyId . '.' . $this->privateKeyId, Encryption::ID);
	}

	/**
	 * @param string $keyId
	 * @param string $key
	 * @return string returns openssl key
	 */
	public function setSystemPrivateKey($keyId, $key) {
		return $this->keyStorage->setSystemUserKey(
			$keyId . '.' . $this->privateKeyId,
			$key,
			Encryption::ID);
	}

	/**
	 * add system keys such as the public share key and the recovery key
	 *
	 * @param array $accessList
	 * @param array $publicKeys
	 * @param string $uid
	 * @return array
	 * @throws PublicKeyMissingException
	 */
	public function addSystemKeys(array $accessList, array $publicKeys, $uid) {
		if (!empty($accessList['public'])) {
			$publicShareKey = $this->getPublicShareKey();
			if (empty($publicShareKey)) {
				throw new PublicKeyMissingException($this->getPublicShareKeyId());
			}
			$publicKeys[$this->getPublicShareKeyId()] = $publicShareKey;
		}

		if ($this->recoveryKeyExists() &&
			$this->util->isRecoveryEnabledForUser($uid)) {
			$publicKeys[$this->getRecoveryKeyId()] = $this->getRecoveryKey();
		}

		return $publicKeys;
	}

	/**
	 * get master key password
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getMasterKeyPassword() {
		$password = $this->config->getSystemValue('secret');
		if (empty($password)) {
			throw new \Exception('Can not get secret from Nextcloud instance');
		}

		return $password;
	}

	/**
	 * return master key id
	 *
	 * @return string
	 */
	public function getMasterKeyId() {
		return $this->masterKeyId;
	}

	/**
	 * get public master key
	 *
	 * @return string
	 */
	public function getPublicMasterKey() {
		return $this->keyStorage->getSystemUserKey($this->masterKeyId . '.' . $this->publicKeyId, Encryption::ID);
	}

	/**
	 * get public master key
	 *
	 * @return string
	 */
	public function getPrivateMasterKey() {
		return $this->keyStorage->getSystemUserKey($this->masterKeyId . '.' . $this->privateKeyId, Encryption::ID);
	}
}
