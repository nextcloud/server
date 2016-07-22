<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
use OC\Files\View;
use OCP\Encryption\IFile;

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
	/**
	 * @var View
	 */
	private $view;
	/**
	 * @var IFile
	 */
	private $file;

	/**
	 * @param IUserSession $user
	 * @param Crypt $crypt
	 * @param ISecureRandom $random
	 * @param KeyManager $keyManager
	 * @param IConfig $config
	 * @param IStorage $keyStorage
	 * @param IFile $file
	 * @param View $view
	 */
	public function __construct(IUserSession $user,
								Crypt $crypt,
								ISecureRandom $random,
								KeyManager $keyManager,
								IConfig $config,
								IStorage $keyStorage,
								IFile $file,
								View $view) {
		$this->user = ($user && $user->isLoggedIn()) ? $user->getUser() : false;
		$this->crypt = $crypt;
		$this->random = $random;
		$this->keyManager = $keyManager;
		$this->config = $config;
		$this->keyStorage = $keyStorage;
		$this->view = $view;
		$this->file = $file;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function enableAdminRecovery($password) {
		$appConfig = $this->config;
		$keyManager = $this->keyManager;

		if (!$keyManager->recoveryKeyExists()) {
			$keyPair = $this->crypt->createKeyPair();
			if(!is_array($keyPair)) {
				return false;
			}

			$this->keyManager->setRecoveryKey($password, $keyPair);
		}

		if ($keyManager->checkRecoveryPassword($password)) {
			$appConfig->setAppValue('encryption', 'recoveryAdminEnabled', 1);
			return true;
		}

		return false;
	}

	/**
	 * change recovery key id
	 *
	 * @param string $newPassword
	 * @param string $oldPassword
	 * @return bool
	 */
	public function changeRecoveryKeyPassword($newPassword, $oldPassword) {
		$recoveryKey = $this->keyManager->getSystemPrivateKey($this->keyManager->getRecoveryKeyId());
		$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey, $oldPassword);
		if($decryptedRecoveryKey === false) {
			return false;
		}
		$encryptedRecoveryKey = $this->crypt->encryptPrivateKey($decryptedRecoveryKey, $newPassword);
		$header = $this->crypt->generateHeader();
		if ($encryptedRecoveryKey) {
			$this->keyManager->setSystemPrivateKey($this->keyManager->getRecoveryKeyId(), $header . $encryptedRecoveryKey);
			return true;
		}
		return false;
	}

	/**
	 * @param string $recoveryPassword
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

	/**
	 * check if recovery is enabled for user
	 *
	 * @param string $user if no user is given we check the current logged-in user
	 *
	 * @return bool
	 */
	public function isRecoveryEnabledForUser($user = '') {
		$uid = empty($user) ? $this->user->getUID() : $user;
		$recoveryMode = $this->config->getUserValue($uid,
			'encryption',
			'recoveryEnabled',
			0);

		return ($recoveryMode === '1');
	}

	/**
	 * check if recovery is key is enabled by the administrator
	 *
	 * @return bool
	 */
	public function isRecoveryKeyEnabled() {
		$enabled = $this->config->getAppValue('encryption', 'recoveryAdminEnabled', 0);

		return ($enabled === '1');
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	public function setRecoveryForUser($value) {

		try {
			$this->config->setUserValue($this->user->getUID(),
				'encryption',
				'recoveryEnabled',
				$value);

			if ($value === '1') {
				$this->addRecoveryKeys('/' . $this->user->getUID() . '/files/');
			} else {
				$this->removeRecoveryKeys('/' . $this->user->getUID() . '/files/');
			}

			return true;
		} catch (PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * add recovery key to all encrypted files
	 * @param string $path
	 */
	private function addRecoveryKeys($path) {
		$dirContent = $this->view->getDirectoryContent($path);
		foreach ($dirContent as $item) {
			$filePath = $item->getPath();
			if ($item['type'] === 'dir') {
				$this->addRecoveryKeys($filePath . '/');
			} else {
				$fileKey = $this->keyManager->getFileKey($filePath, $this->user->getUID());
				if (!empty($fileKey)) {
					$accessList = $this->file->getAccessList($filePath);
					$publicKeys = array();
					foreach ($accessList['users'] as $uid) {
						$publicKeys[$uid] = $this->keyManager->getPublicKey($uid);
					}

					$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $this->user->getUID());

					$encryptedKeyfiles = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);
					$this->keyManager->setAllFileKeys($filePath, $encryptedKeyfiles);
				}
			}
		}
	}

	/**
	 * remove recovery key to all encrypted files
	 * @param string $path
	 */
	private function removeRecoveryKeys($path) {
		$dirContent = $this->view->getDirectoryContent($path);
		foreach ($dirContent as $item) {
			$filePath = $item->getPath();
			if ($item['type'] === 'dir') {
				$this->removeRecoveryKeys($filePath . '/');
			} else {
				$this->keyManager->deleteShareKey($filePath, $this->keyManager->getRecoveryKeyId());
			}
		}
	}

	/**
	 * recover users files with the recovery key
	 *
	 * @param string $recoveryPassword
	 * @param string $user
	 */
	public function recoverUsersFiles($recoveryPassword, $user) {
		$encryptedKey = $this->keyManager->getSystemPrivateKey($this->keyManager->getRecoveryKeyId());

		$privateKey = $this->crypt->decryptPrivateKey($encryptedKey, $recoveryPassword);
		if($privateKey !== false) {
			$this->recoverAllFiles('/' . $user . '/files/', $privateKey, $user);
		}
	}

	/**
	 * recover users files
	 *
	 * @param string $path
	 * @param string $privateKey
	 * @param string $uid
	 */
	private function recoverAllFiles($path, $privateKey, $uid) {
		$dirContent = $this->view->getDirectoryContent($path);

		foreach ($dirContent as $item) {
			// Get relative path from encryption/keyfiles
			$filePath = $item->getPath();
			if ($this->view->is_dir($filePath)) {
				$this->recoverAllFiles($filePath . '/', $privateKey, $uid);
			} else {
				$this->recoverFile($filePath, $privateKey, $uid);
			}
		}

	}

	/**
	 * recover file
	 *
	 * @param string $path
	 * @param string $privateKey
	 * @param string $uid
	 */
	private function recoverFile($path, $privateKey, $uid) {
		$encryptedFileKey = $this->keyManager->getEncryptedFileKey($path);
		$shareKey = $this->keyManager->getShareKey($path, $this->keyManager->getRecoveryKeyId());

		if ($encryptedFileKey && $shareKey && $privateKey) {
			$fileKey = $this->crypt->multiKeyDecrypt($encryptedFileKey,
				$shareKey,
				$privateKey);
		}

		if (!empty($fileKey)) {
			$accessList = $this->file->getAccessList($path);
			$publicKeys = array();
			foreach ($accessList['users'] as $user) {
				$publicKeys[$user] = $this->keyManager->getPublicKey($user);
			}

			$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $uid);

			$encryptedKeyfiles = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);
			$this->keyManager->setAllFileKeys($path, $encryptedKeyfiles);
		}

	}


}
