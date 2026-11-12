<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Encryption;

use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCP\Config\IUserConfig;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

class Util {
	private IUser|false $user;

	public function __construct(
		private View $files,
		private Crypt $crypt,
		IUserSession $userSession,
		private IAppConfig $appConfig,
		private IUserConfig $userConfig,
		private IUserManager $userManager,
	) {
		$this->user = $userSession->isLoggedIn() ? $userSession->getUser() : false;
	}

	/**
	 * check if recovery key is enabled for user
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function isRecoveryEnabledForUser($uid) {
		return $this->userConfig->getValueBool($uid, 'encryption', 'recoveryEnabled');
	}

	/**
	 * check if the home storage should be encrypted
	 *
	 * @return bool
	 */
	public function shouldEncryptHomeStorage() {
		return $this->appConfig->getValueBool('encryption', 'encryptHomeStorage', true);
	}

	/**
	 * set the home storage encryption on/off
	 *
	 * @param bool $encryptHomeStorage
	 */
	public function setEncryptHomeStorage(bool $encryptHomeStorage) {
		$this->appConfig->setValueBool('encryption', 'encryptHomeStorage', $encryptHomeStorage);
	}

	/**
	 * check if master key is enabled
	 */
	public function isMasterKeyEnabled(): bool {
		return $this->appConfig->getValueBool('encryption', 'useMasterKey', true);
	}

	public function setRecoveryForUser(bool $enabled): bool {
		try {
			$this->userConfig->setValueBool($this->user->getUID(), 'encryption', 'recoveryEnabled', $enabled);
			return true;
		} catch (PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * @param string $uid
	 * @return bool
	 */
	public function userHasFiles($uid) {
		return $this->files->file_exists($uid . '/files');
	}

	/**
	 * Get the owner based on the path.
	 *
	 * The user must exist at call time.
	 *
	 * @param string $path Virtual path, e.g. /alice/files/report.txt
	 * @return string UID, e.g. alice
	 * @throws \BadMethodCallException if the path is malformed or its owner does not exist
	 */
	public function getOwner(string $path): string {
		if (!str_starts_with($path, '/')) {
			throw new \BadMethodCallException(
				'Malformed path: Expected a path rooted at the data directory, e.g. /alice/files/report.txt'
			);
		}

		$parts = explode('/', $path, 3);
		$owner = $parts[1] ?? '';

		if ($owner === '') {
			throw new \BadMethodCallException(
				'Malformed path: Expected a path rooted at the data directory, e.g. /alice/files/report.txt'
			);
		}

		if ($this->userManager->userExists($owner) === false) {
			// The UID may no longer exist in any backend, or the path may be malformed.
			throw new \BadMethodCallException(
				'Cannot determine owner: The path may be malformed or its user may no longer exist'
			);
		}

		return $owner;
	}

	public function getStorage(string $path): ?IStorage {
		return $this->files->getMount($path)->getStorage();
	}

}
