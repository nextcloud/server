<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 1:20 PM
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
use OC\Encryption\Exceptions\PrivateKeyMissingException;
use OC\Encryption\Exceptions\PublicKeyMissingException;
use OCA\Encryption\Crypto\Crypt;
use OCP\Encryption\Keys\IStorage;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserSession;
use \OCP\ISession;

class KeyManager {

	/**
	 * @var ISession
	 */
	public static $session;
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
	 * @param IStorage $keyStorage
	 * @param Crypt $crypt
	 * @param IConfig $config
	 * @param IUserSession $userSession
	 * @param \OCP\ISession $session
	 * @param ILogger $log
	 */
	public function __construct(IStorage $keyStorage, Crypt $crypt, IConfig $config, IUserSession $userSession, ISession $session, ILogger $log) {

		self::$session = $session;
		$this->keyStorage = $keyStorage;
		$this->crypt = $crypt;
		$this->config = $config;
		$this->recoveryKeyId = $this->config->getAppValue('encryption',
			'recoveryKeyId');
		$this->publicShareKeyId = $this->config->getAppValue('encryption',
			'publicShareKeyId');
		$this->keyId = $userSession && $userSession->isLoggedIn() ? $userSession->getUser()->getUID() : false;
		$this->log = $log;
	}

	/**
	 * @return bool
	 */
	public function recoveryKeyExists() {
		return (strlen($this->keyStorage->getSystemUserKey($this->recoveryKeyId)) !== 0);
	}

	/**
	 * @param $password
	 * @return bool
	 */
	public function checkRecoveryPassword($password) {
		$recoveryKey = $this->keyStorage->getSystemUserKey($this->recoveryKeyId);
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
			$this->config->setAppValue('encryption', 'recoveryAdminEnabled', 1);
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
	 * @return ISession
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


		self::$session->set('privateKey', $privateKey);
		self::$session->set('initStatus', true);

		return self::$session;
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
		throw new PrivateKeyMissingException();
	}

	/**
	 * @param $path
	 * @param $uid
	 * @return string
	 */
	public function getFileKey($path, $uid) {
		$key = '';
		$encryptedFileKey = $this->keyStorage->getFileKey($path,
			$this->fileKeyId);
		$shareKey = $this->getShareKey($path, $uid);
		$privateKey = $this->session->get('privateKey');

		if ($encryptedFileKey && $shareKey && $privateKey) {
			$key = $this->crypt->multiKeyDecrypt($encryptedFileKey,
				$shareKey,
				$privateKey);
		}

		return $key;
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
	 * Change a user's encryption passphrase
	 *
	 * @param array $params keys: uid, password
	 * @param IUserSession $user
	 * @param Util $util
	 * @return bool
	 */
	public function setPassphrase($params, IUserSession $user, Util $util) {

		// Get existing decrypted private key
		$privateKey = self::$cacheFactory->get('privateKey');

		if ($params['uid'] === $user->getUser()->getUID() && $privateKey) {

			// Encrypt private key with new user pwd as passphrase
			$encryptedPrivateKey = $this->crypt->symmetricEncryptFileContent($privateKey,
				$params['password']);

			// Save private key
			if ($encryptedPrivateKey) {
				$this->setPrivateKey($user->getUser()->getUID(),
					$encryptedPrivateKey);
			} else {
				$this->log->error('Encryption could not update users encryption password');
			}

			// NOTE: Session does not need to be updated as the
			// private key has not changed, only the passphrase
			// used to decrypt it has changed
		} else { // admin changed the password for a different user, create new keys and reencrypt file keys
			$user = $params['uid'];
			$recoveryPassword = isset($params['recoveryPassword']) ? $params['recoveryPassword'] : null;

			// we generate new keys if...
			// ...we have a recovery password and the user enabled the recovery key
			// ...encryption was activated for the first time (no keys exists)
			// ...the user doesn't have any files
			if (($util->recoveryEnabledForUser() && $recoveryPassword) || !$this->userHasKeys($user) || !$util->userHasFiles($user)
			) {

				// backup old keys
				$this->backupAllKeys('recovery');

				$newUserPassword = $params['password'];

				$keypair = $this->crypt->createKeyPair();

				// Disable encryption proxy to prevent recursive calls
				$proxyStatus = \OC_FileProxy::$enabled;
				\OC_FileProxy::$enabled = false;

				// Save public key
				$this->setPublicKey($user, $keypair['publicKey']);

				// Encrypt private key with new password
				$encryptedKey = $this->crypt->symmetricEncryptFileContent($keypair['privateKey'],
					$newUserPassword);

				if ($encryptedKey) {
					$this->setPrivateKey($user, $encryptedKey);

					if ($recoveryPassword) { // if recovery key is set we can re-encrypt the key files
						$util->recoverUsersFiles($recoveryPassword);
					}
				} else {
					$this->log->error('Encryption Could not update users encryption password');
				}

				\OC_FileProxy::$enabled = $proxyStatus;
			}
		}
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
		throw new PublicKeyMissingException();
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
	 * @return string returns openssl key
	 */
	public function getSystemPrivateKey() {
		return $this->keyStorage->getSystemUserKey($this->privateKeyId);
	}
}
