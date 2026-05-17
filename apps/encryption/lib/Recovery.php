<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCP\Config\IUserConfig;
use OCP\Encryption\IFile;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

class Recovery {
	/**
	 * @var null|IUser
	 */
	protected $user;

	public function __construct(
		IUserSession $userSession,
		protected Crypt $crypt,
		private KeyManager $keyManager,
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private IFile $file,
		private View $view,
	) {
		$this->user = ($userSession->isLoggedIn()) ? $userSession->getUser() : null;
	}

	/**
	 * @param string $password
	 * @return bool
	 */
	public function enableAdminRecovery($password) {
		if (!$this->keyManager->recoveryKeyExists()) {
			$keyPair = $this->crypt->createKeyPair();
			if (!is_array($keyPair)) {
				return false;
			}

			$this->keyManager->setRecoveryKey($password, $keyPair);
		}

		if ($this->keyManager->checkRecoveryPassword($password)) {
			$this->appConfig->setValueBool('encryption', 'recoveryAdminEnabled', true);
			return true;
		}

		return false;
	}

	/**
	 * change recovery key id
	 */
	public function changeRecoveryKeyPassword(string $newPassword, string $oldPassword): bool {
		$recoveryKey = $this->keyManager->getSystemPrivateKey($this->keyManager->getRecoveryKeyId());
		$decryptedRecoveryKey = $this->crypt->decryptPrivateKey($recoveryKey, $oldPassword);
		if ($decryptedRecoveryKey === false) {
			return false;
		}
		$encryptedRecoveryKey = $this->crypt->encryptPrivateKey($decryptedRecoveryKey, $newPassword);
		$header = $this->crypt->generateHeader();
		if ($encryptedRecoveryKey !== false) {
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
			$this->appConfig->setValueBool('encryption', 'recoveryAdminEnabled', false);
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
		$uid = $user === '' ? $this->user->getUID() : $user;
		return $this->userConfig->getValueBool($uid, 'encryption', 'recoveryEnabled');
	}

	/**
	 * check if recovery is key is enabled by the administrator
	 *
	 * @return bool
	 */
	public function isRecoveryKeyEnabled() {
		return $this->appConfig->getValueBool('encryption', 'recoveryAdminEnabled');
	}

	/**
	 * @param bool $value
	 * @return bool
	 */
	public function setRecoveryForUser(bool $value): bool {
		try {
			$this->userConfig->setValueBool($this->user->getUID(), 'encryption', 'recoveryEnabled', $value);

			if ($value) {
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
	 */
	private function addRecoveryKeys(string $path): void {
		$dirContent = $this->view->getDirectoryContent($path);
		foreach ($dirContent as $item) {
			$filePath = $item->getPath();
			if ($item['type'] === 'dir') {
				$this->addRecoveryKeys($filePath . '/');
			} else {
				$fileKey = $this->keyManager->getFileKey($filePath, null);
				if (!empty($fileKey)) {
					$accessList = $this->file->getAccessList($filePath);
					$publicKeys = [];
					foreach ($accessList['users'] as $uid) {
						$publicKeys[$uid] = $this->keyManager->getPublicKey($uid);
					}

					$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $this->user->getUID());

					$shareKeys = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);
					$this->keyManager->deleteLegacyFileKey($filePath);
					foreach ($shareKeys as $uid => $keyFile) {
						$this->keyManager->setShareKey($filePath, $uid, $keyFile);
					}
				}
			}
		}
	}

	/**
	 * remove recovery key to all encrypted files
	 */
	private function removeRecoveryKeys(string $path): void {
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
	 */
	public function recoverUsersFiles(string $recoveryPassword, string $user): void {
		$encryptedKey = $this->keyManager->getSystemPrivateKey($this->keyManager->getRecoveryKeyId());

		$privateKey = $this->crypt->decryptPrivateKey($encryptedKey, $recoveryPassword);
		if ($privateKey !== false) {
			$this->recoverAllFiles('/' . $user . '/files/', $privateKey, $user);
		}
	}

	/**
	 * recover users files
	 */
	private function recoverAllFiles(string $path, string $privateKey, string $uid): void {
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
	 */
	private function recoverFile(string $path, string $privateKey, string $uid): void {
		$encryptedFileKey = $this->keyManager->getEncryptedFileKey($path);
		$shareKey = $this->keyManager->getShareKey($path, $this->keyManager->getRecoveryKeyId());

		if ($encryptedFileKey && $shareKey && $privateKey) {
			$fileKey = $this->crypt->multiKeyDecryptLegacy($encryptedFileKey,
				$shareKey,
				$privateKey);
		} elseif ($shareKey && $privateKey) {
			$fileKey = $this->crypt->multiKeyDecrypt($shareKey, $privateKey);
		}

		if (!empty($fileKey)) {
			$accessList = $this->file->getAccessList($path);
			$publicKeys = [];
			foreach ($accessList['users'] as $user) {
				$publicKeys[$user] = $this->keyManager->getPublicKey($user);
			}

			$publicKeys = $this->keyManager->addSystemKeys($accessList, $publicKeys, $uid);

			$shareKeys = $this->crypt->multiKeyEncrypt($fileKey, $publicKeys);
			$this->keyManager->deleteLegacyFileKey($path);
			foreach ($shareKeys as $uid => $keyFile) {
				$this->keyManager->setShareKey($path, $uid, $keyFile);
			}
		}
	}
}
