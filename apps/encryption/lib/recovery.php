<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 2/19/15, 11:45 AM
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


use OCA\Encryption\Crypto\Crypt;
use OCP\Encryption\Keys\IStorage;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;
use OCP\Security\ISecureRandom;
use OCP\Share;

class Recovery {


	/**
	 * @var null|IUser
	 */
	protected $user;
	/**
	 * @var Crypt
	 */
	protected $crypt;
	/**
	 * @var ISecureRandom
	 */
	private $random;
	/**
	 * @var KeyManager
	 */
	private $keyManager;
	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IStorage
	 */
	private $keyStorage;

	private $recoveryKeyId;

	/**
	 * @param IUserSession $user
	 * @param Crypt $crypt
	 * @param ISecureRandom $random
	 * @param KeyManager $keyManager
	 * @param IConfig $config
	 * @param IStorage $keyStorage
	 */
	public function __construct(IUserSession $user,
								Crypt $crypt,
								ISecureRandom $random,
								KeyManager $keyManager,
								IConfig $config,
								IStorage $keyStorage) {
		$this->user = $user && $user->isLoggedIn() ? $user->getUser() : false;
		$this->crypt = $crypt;
		$this->random = $random;
		$this->keyManager = $keyManager;
		$this->config = $config;
		$this->keyStorage = $keyStorage;
	}

	/**
	 * @param $recoveryKeyId
	 * @param $password
	 * @return bool
	 */
	public function enableAdminRecovery($recoveryKeyId, $password) {
		$appConfig = $this->config;

		if ($recoveryKeyId === null) {
			$recoveryKeyId = $this->random->getLowStrengthGenerator();
			$appConfig->setAppValue('encryption',
				'recoveryKeyId',
				$recoveryKeyId);
		}

		$keyManager = $this->keyManager;

		if (!$keyManager->recoveryKeyExists()) {
			$keyPair = $this->crypt->createKeyPair();

			return $this->keyManager->storeKeyPair($this->user->getUID(),
				$password,
				$keyPair);
		}

		if ($keyManager->checkRecoveryPassword($password)) {
			$appConfig->setAppValue('encryption', 'recoveryAdminEnabled', 1);
			return true;
		}

		return false;
	}

	/**
	 * @param $recoveryPassword
	 * @return bool
	 */
	public function disableAdminRecovery($recoveryPassword) {
		$keyManager = $this->keyManager;

		if ($keyManager->checkRecoveryPassword($recoveryPassword)) {
			// Set recoveryAdmin as disabled
			$this->config->setAppValue('encryption', 'recoveryAdminEnabled', 0);
			return true;
		}
		return false;
	}

	public function addRecoveryKeys($keyId) {
		// No idea new way to do this....
	}

	public function removeRecoveryKeys() {
		// No idea new way to do this....
	}

	/**
	 * @return bool
	 */
	public function recoveryEnabledForUser() {
		$recoveryMode = $this->config->getUserValue($this->user->getUID(),
			'encryption',
			'recoveryEnabled',
			0);

		return ($recoveryMode === '1');
	}

	/**
	 * @param $enabled
	 * @return bool
	 */
	public function setRecoveryForUser($enabled) {
		$value = $enabled ? '1' : '0';

		try {
			$this->config->setUserValue($this->user->getUID(),
				'encryption',
				'recoveryEnabled',
				$value);
			return true;
		} catch (PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * @param $recoveryPassword
	 */
	public function recoverUsersFiles($recoveryPassword) {
		$encryptedKey = $this->keyManager->getSystemPrivateKey();

		$privateKey = $this->crypt->decryptPrivateKey($encryptedKey,
			$recoveryPassword);

		$this->recoverAllFiles('/', $privateKey);
	}

	/**
	 * @param $path
	 * @param $privateKey
	 */
	private function recoverAllFiles($path, $privateKey) {
		$dirContent = $this->files->getDirectoryContent($path);

		foreach ($dirContent as $item) {
			// Get relative path from encryption/keyfiles
			$filePath = substr($item['path'], strlen('encryption/keys'));
			if ($this->files->is_dir($this->user->getUID() . '/files' . '/' . $filePath)) {
				$this->recoverAllFiles($filePath . '/', $privateKey);
			} else {
				$this->recoverFile($filePath, $privateKey);
			}
		}

	}

	/**
	 * @param $filePath
	 * @param $privateKey
	 */
	private function recoverFile($filePath, $privateKey) {
		$sharingEnabled = Share::isEnabled();
		$uid = $this->user->getUID();

		// Find out who, if anyone, is sharing the file
		if ($sharingEnabled) {
			$result = Share::getUsersSharingFile($filePath,
				$uid,
				true);
			$userIds = $result['users'];
			$userIds[] = 'public';
		} else {
			$userIds = [
				$uid,
				$this->recoveryKeyId
			];
		}
		$filteredUids = $this->filterShareReadyUsers($userIds);

		// Decrypt file key
		$encKeyFile = $this->keyManager->getFileKey($filePath,
			$uid);

		$shareKey = $this->keyManager->getShareKey($filePath,
			$uid);

		$plainKeyFile = $this->crypt->multiKeyDecrypt($encKeyFile,
			$shareKey,
			$privateKey);

		// Encrypt the file key again to all users, this time with the new publick keyt for the recovered user
		$userPublicKeys = $this->keyManager->getPublicKeys($filteredUids['ready']);
		$multiEncryptionKey = $this->crypt->multiKeyEncrypt($plainKeyFile,
			$userPublicKeys);

		$this->keyManager->setFileKey($multiEncryptionKey['data'],
			$uid);

		$this->keyManager->setShareKey($filePath,
			$uid,
			$multiEncryptionKey['keys']);
	}


}
