<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OCA\Encryption;

use OC\Encryption\Exceptions\DecryptionFailedException;
use OCA\Encryption\Exceptions\PrivateKeyMissingException;
use OCA\Encryption\Exceptions\PublicKeyMissingException;
use OCA\Encryption\Crypto\Crypt;
use OCP\Encryption\Keys\IStorage;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserSession;

class KeyManager {

	/**
	 * @var Session
	 */
	protected $session;
	/**
	 * @var IStorage
	 */
	private $keyStorage;
	/**
	 * @var Crypt
	 */
	private $crypt;
	/**
	 * @var string
	 */
	private $recoveryKeyId;
	/**
	 * @var string
	 */
	private $publicShareKeyId;
	/**
	 * @var string UserID
	 */
	private $keyId;
	/**
	 * @var string
	 */
	private $publicKeyId = 'publicKey';
	/**
	 * @var string
	 */
	private $privateKeyId = 'privateKey';

	/**
	 * @var string
	 */
	private $shareKeyId = 'shareKey';

	/**
	 * @var string
	 */
	private $fileKeyId = 'fileKey';
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var ILogger
	 */
	private $log;
	/**
	 * @var Util
	 */
	private $util;

	/**
	 * @param IStorage $keyStorage
	 * @param Crypt $crypt
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param Session $session
	 * @param ILogger $log
	 * @param Util $util
	 */
	public function __construct(
		IStorage $keyStorage,
		Crypt $crypt,
		IConfig $config,
		IUserSession $userSession,
		Session $session,
		ILogger $log,
		Util $util
	) {

		$this->util = $util;
		$this->session = $session;
		$this->keyStorage = $keyStorage;
		$this->crypt = $crypt;
		$this->config = $config;
		$this->log = $log;

		$this->recoveryKeyId = $this->config->getAppValue('encryption',
			'recoveryKeyId');
		if (empty($this->recoveryKeyId)) {
			$this->recoveryKeyId = 'recoveryKey_' . substr(md5(time()), 0, 8);
			$this->config->setAppValue('encryption',
				'recoveryKeyId',
				$this->recoveryKeyId);
		}

		$this->publicShareKeyId = $this->config->getAppValue('encryption',
			'publicShareKeyId');
		if (empty($this->publicShareKeyId)) {
			$this->publicShareKeyId = 'pubShare_' . substr(md5(time()), 0, 8);
			$this->config->setAppValue('encryption', 'publicShareKeyId', $this->publicShareKeyId);
		}

		$shareKey = $this->getPublicShareKey();
		if (empty($shareKey)) {
			$keyPair = $this->crypt->createKeyPair();

			// Save public key
			$this->keyStorage->setSystemUserKey(
				$this->publicShareKeyId . '.publicKey', $keyPair['publicKey']);

			// Encrypt private key empty passphrase
			$encryptedKey = $this->crypt->symmetricEncryptFileContent($keyPair['privateKey'], '');
			$this->keyStorage->setSystemUserKey($this->publicShareKeyId . '.privateKey', $encryptedKey);
		}

		$this->keyId = $userSession && $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : false;
		$this->log = $log;
	}

	/**
	 * @return bool
	 */
	public function recoveryKeyExists() {
		$key = $this->getRecoveryKey();
		return (!empty($key));
	}

	/**
	 * get recovery key
	 *
	 * @return string
	 */
	public function getRecoveryKey() {
		return $this->keyStorage->getSystemUserKey($this->recoveryKeyId . '.publicKey');
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
	 * @param $password
	 * @return bool
	 */
	public function checkRecoveryPassword($password) {
		$recoveryKey = $this->keyStorage->getSystemUserKey($this->recoveryKeyId . '.privateKey');
		$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey,
			$password);

		if ($decryptedRecoveryKey) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @param string $keyPair
	 * @return bool
	 */
	public function storeKeyPair($uid, $password, $keyPair) {
		// Save Public Key
		$this->setPublicKey($uid, $keyPair['publicKey']);

		$encryptedKey = $this->crypt->symmetricEncryptFileContent($keyPair['privateKey'],
			$password);

		if ($encryptedKey) {
			$this->setPrivateKey($uid, $encryptedKey);
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
		$this->keyStorage->setSystemUserKey($this->getRecoveryKeyId(). '.publicKey', $keyPair['publicKey']);

		$encryptedKey = $this->crypt->symmetricEncryptFileContent($keyPair['privateKey'],
			$password);

		if ($encryptedKey) {
			$this->setSystemPrivateKey($this->getRecoveryKeyId(), $encryptedKey);
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
		return $this->keyStorage->setUserKey($userId, $this->publicKeyId, $key);
	}

	/**
	 * @param $userId
	 * @param $key
	 * @return bool
	 */
	public function setPrivateKey($userId, $key) {
		return $this->keyStorage->setUserKey($userId,
			$this->privateKeyId,
			$key);
	}

	/**
	 * write file key to key storage
	 *
	 * @param string $path
	 * @param string $key
	 * @return boolean
	 */
	public function setFileKey($path, $key) {
		return $this->keyStorage->setFileKey($path, $this->fileKeyId, $key);
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
		return $this->keyStorage->setFileKey($path, $keyId, $key);
	}

	/**
	 * Decrypt private key and store it
	 *
	 * @param string $uid userid
	 * @param string $passPhrase users password
	 * @return boolean
	 */
	public function init($uid, $passPhrase) {
		try {
			$privateKey = $this->getPrivateKey($uid);
			$privateKey = $this->crypt->decryptPrivateKey($privateKey,
				$passPhrase);
		} catch (PrivateKeyMissingException $e) {
			return false;
		} catch (DecryptionFailedException $e) {
			return false;
		}

		$this->session->setPrivateKey($privateKey);
		$this->session->setStatus(Session::INIT_SUCCESSFUL);

		return true;
	}

	/**
	 * @param $userId
	 * @return mixed
	 * @throws PrivateKeyMissingException
	 */
	public function getPrivateKey($userId) {
		$privateKey = $this->keyStorage->getUserKey($userId,
			$this->privateKeyId);

		if (strlen($privateKey) !== 0) {
			return $privateKey;
		}
		throw new PrivateKeyMissingException($userId);
	}

	/**
	 * @param $path
	 * @param $uid
	 * @return string
	 */
	public function getFileKey($path, $uid) {
		$encryptedFileKey = $this->keyStorage->getFileKey($path, $this->fileKeyId);

		if (is_null($uid)) {
			$uid = $this->getPublicShareKeyId();
			$shareKey = $this->getShareKey($path, $uid);
			$privateKey = $this->keyStorage->getSystemUserKey($this->publicShareKeyId . '.privateKey');
			$privateKey = $this->crypt->symmetricDecryptFileContent($privateKey);
		} else {
			$shareKey = $this->getShareKey($path, $uid);
			$privateKey = $this->session->getPrivateKey();
		}

		if ($encryptedFileKey && $shareKey && $privateKey) {
			return $this->crypt->multiKeyDecrypt($encryptedFileKey,
				$shareKey,
				$privateKey);
		}

		return '';
	}

	/**
	 * get the encrypted file key
	 *
	 * @param $path
	 * @return string
	 */
	public function getEncryptedFileKey($path) {
		$encryptedFileKey = $this->keyStorage->getFileKey($path,
			$this->fileKeyId);

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
		return $this->keyStorage->deleteFileKey($path, $keyId . '.' . $this->shareKeyId);
	}


	/**
	 * @param $path
	 * @param $uid
	 * @return mixed
	 */
	public function getShareKey($path, $uid) {
		$keyId = $uid . '.' . $this->shareKeyId;
		return $this->keyStorage->getFileKey($path, $keyId);
	}

	/**
	 * @param $userId
	 * @return bool
	 */
	public function userHasKeys($userId) {
		try {
			$this->getPrivateKey($userId);
			$this->getPublicKey($userId);
		} catch (PrivateKeyMissingException $e) {
			return false;
		} catch (PublicKeyMissingException $e) {
			return false;
		}
		return true;
	}

	/**
	 * @param $userId
	 * @return mixed
	 * @throws PublicKeyMissingException
	 */
	public function getPublicKey($userId) {
		$publicKey = $this->keyStorage->getUserKey($userId, $this->publicKeyId);

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
		return $this->keyStorage->getSystemUserKey($this->publicShareKeyId . '.publicKey');
	}

	/**
	 * @param $purpose
	 * @param bool $timestamp
	 * @param bool $includeUserKeys
	 */
	public function backupAllKeys($purpose, $timestamp = true, $includeUserKeys = true) {
//		$backupDir = $this->keyStorage->;
	}

	/**
	 * @param string $uid
	 */
	public function replaceUserKeys($uid) {
		$this->backupAllKeys('password_reset');
		$this->deletePublicKey($uid);
		$this->deletePrivateKey($uid);
	}

	/**
	 * @param $uid
	 * @return bool
	 */
	public function deletePublicKey($uid) {
		return $this->keyStorage->deleteUserKey($uid, $this->publicKeyId);
	}

	/**
	 * @param $uid
	 * @return bool
	 */
	private function deletePrivateKey($uid) {
		return $this->keyStorage->deleteUserKey($uid, $this->privateKeyId);
	}

	public function deleteAllFileKeys($path) {
		return $this->keyStorage->deleteAllFileKeys($path);
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
		return $this->keyStorage->getSystemUserKey($keyId . '.' . $this->privateKeyId);
	}

	/**
	 * @param string $keyId
	 * @param string $key
	 * @return string returns openssl key
	 */
	public function setSystemPrivateKey($keyId, $key) {
		return $this->keyStorage->setSystemUserKey($keyId . '.' . $this->privateKeyId, $key);
	}

	/**
	 * add system keys such as the public share key and the recovery key
	 *
	 * @param array $accessList
	 * @param array $publicKeys
	 * @return array
	 * @throws PublicKeyMissingException
	 */
	public function addSystemKeys(array $accessList, array $publicKeys) {
		if (!empty($accessList['public'])) {
			$publicShareKey = $this->getPublicShareKey();
			if (empty($publicShareKey)) {
				throw new PublicKeyMissingException($this->getPublicShareKeyId());
			}
			$publicKeys[$this->getPublicShareKeyId()] = $publicShareKey;
		}

		if ($this->recoveryKeyExists() &&
			$this->util->isRecoveryEnabledForUser()) {

			$publicKeys[$this->getRecoveryKeyId()] = $this->getRecoveryKey();
		}

		return $publicKeys;
	}
}
