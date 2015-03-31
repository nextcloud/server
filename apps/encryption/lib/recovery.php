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
	 * @var string
	 */
	private $recoveryKeyId;

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
	 * @param $recoveryKeyId
	 * @param $password
	 * @return bool
	 */
	public function enableAdminRecovery($password) {
		$appConfig = $this->config;
		$keyManager = $this->keyManager;

		if (!$keyManager->recoveryKeyExists()) {
			$keyPair = $this->crypt->createKeyPair();

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
	 */
	public function changeRecoveryKeyPassword($newPassword, $oldPassword) {
		$recoveryKey = $this->keyManager->getSystemPrivateKey($this->keyManager->getRecoveryKeyId());
		$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey, $oldPassword);
		$encryptedRecoveryKey = $this->crypt->symmetricEncryptFileContent($decryptedRecoveryKey, $newPassword);
		if ($encryptedRecoveryKey) {
			$this->keyManager->setSystemPrivateKey($this->keyManager->getRecoveryKeyId(), $encryptedRecoveryKey);
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
				$this->removeRecoveryKeys();
			}

			return true;
		} catch (PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * add recovery key to all encrypted files
	 */
	private function addRecoveryKeys($path = '/') {
		$dirContent = $this->view->getDirectoryContent($path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles/
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

					$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys);

					$encryptedKeyfiles = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);
					$this->keyManager->setAllFileKeys($filePath, $encryptedKeyfiles);
				}
			}
		}
	}

	/**
	 * remove recovery key to all encrypted files
	 */
	private function removeRecoveryKeys($path = '/') {
		return true;
		$dirContent = $this->view->getDirectoryContent($this->keyfilesPath . $path);
		foreach ($dirContent as $item) {
			// get relative path from files_encryption/keyfiles
			$filePath = substr($item['path'], strlen('files_encryption/keyfiles'));
			if ($item['type'] === 'dir') {
				$this->removeRecoveryKeys($filePath . '/');
			} else {
				// remove '.key' extension from path e.g. 'file.txt.key' to 'file.txt'
				$file = substr($filePath, 0, -4);
				$this->view->unlink($this->shareKeysPath . '/' . $file . '.' . $this->recoveryKeyId . '.shareKey');
			}
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
