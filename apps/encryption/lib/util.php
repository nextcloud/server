<?php
/**
 * @author Clark Tomlinson  <clark@owncloud.com>
 * @since 3/17/15, 10:31 AM
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


use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCA\Files_Versions\Storage;
use OCP\App;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;
use OCP\Share;

class Util {
	/**
	 * @var View
	 */
	private $files;
	/**
	 * @var Filesystem
	 */
	private $filesystem;
	/**
	 * @var Crypt
	 */
	private $crypt;
	/**
	 * @var KeyManager
	 */
	private $keyManager;
	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var bool|IUser
	 */
	private $user;
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Util constructor.
	 *
	 * @param View $files
	 * @param Filesystem $filesystem
	 * @param Crypt $crypt
	 * @param KeyManager $keyManager
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 */
	public function __construct(
		View $files,
		Filesystem $filesystem,
		Crypt $crypt,
		KeyManager $keyManager,
		ILogger $logger,
		IUserSession $userSession,
		IConfig $config
	) {
		$this->files = $files;
		$this->filesystem = $filesystem;
		$this->crypt = $crypt;
		$this->keyManager = $keyManager;
		$this->logger = $logger;
		$this->user = $userSession && $userSession->isLoggedIn() ? $userSession->getUser() : false;
		$this->config = $config;
	}

	/**
	 * @param $filePath
	 * @return array
	 */
	private function splitPath($filePath) {
		$normalized = $this->filesystem->normalizePath($filePath);

		return explode('/', $normalized);
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
	 * @param string $uid
	 * @return bool
	 */
	public function userHasFiles($uid) {
		return $this->files->file_exists($uid . '/files');
	}

	/**
	 * @param $path
	 * @param $privateKey
	 */
	private function recoverAllFiles($path, $privateKey) {
		// Todo relocate to storage
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
