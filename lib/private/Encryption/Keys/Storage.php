<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Encryption\Keys;

use OC\Encryption\Util;
use OC\Files\Filesystem;
use OC\Files\View;
use OC\ServerNotAvailableException;
use OC\User\NoUserException;
use OCP\Encryption\Keys\IStorage;
use OCP\IConfig;
use OCP\Security\ICrypto;

class Storage implements IStorage {
	/** @var string hidden file which indicate that the folder is a valid key storage */
	public const KEY_STORAGE_MARKER = '.oc_key_storage';
	/** @var string base dir where all the file related keys are stored */
	private string $keys_base_dir;
	/** @var string root of the key storage default is empty which means that we use the data folder */
	private string $root_dir;
	private string $encryption_base_dir;
	private string $backup_base_dir;
	private array $keyCache = [];

	public function __construct(
		private readonly View $view,
		private readonly Util $util,
		private readonly ICrypto $crypto,
		private readonly IConfig $config,
	) {
		$this->encryption_base_dir = '/files_encryption';
		$this->keys_base_dir = $this->encryption_base_dir . '/keys';
		$this->backup_base_dir = $this->encryption_base_dir . '/backup';
		$this->root_dir = $this->util->getKeyStorageRoot();
	}

	/**
	 * @inheritdoc
	 */
	public function getUserKey($uid, $keyId, $encryptionModuleId) {
		$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, $uid);
		return base64_decode($this->getKeyWithUid($path, $uid));
	}

	/**
	 * @inheritdoc
	 */
	public function getFileKey($path, $keyId, $encryptionModuleId) {
		$realFile = $this->util->stripPartialFileExtension($path);
		$keyDir = $this->util->getFileKeyDir($encryptionModuleId, $realFile);
		$key = $this->getKey($keyDir . $keyId)['key'];

		if ($key === '' && $realFile !== $path) {
			// Check if the part file has keys and use them, if no normal keys
			// exist. This is required to fix copyBetweenStorage() when we
			// rename a .part file over storage borders.
			$keyDir = $this->util->getFileKeyDir($encryptionModuleId, $path);
			$key = $this->getKey($keyDir . $keyId)['key'];
		}

		return base64_decode($key);
	}

	/**
	 * @inheritdoc
	 */
	public function getSystemUserKey($keyId, $encryptionModuleId) {
		$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, null);
		return base64_decode($this->getKeyWithUid($path, null));
	}

	/**
	 * @inheritdoc
	 */
	public function setUserKey($uid, $keyId, $key, $encryptionModuleId) {
		$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, $uid);
		return $this->setKey($path, [
			'key' => base64_encode($key),
			'uid' => $uid,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function setFileKey($path, $keyId, $key, $encryptionModuleId) {
		$keyDir = $this->util->getFileKeyDir($encryptionModuleId, $path);
		return $this->setKey($keyDir . $keyId, [
			'key' => base64_encode($key),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function setSystemUserKey($keyId, $key, $encryptionModuleId) {
		$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, null);
		return $this->setKey($path, [
			'key' => base64_encode($key),
			'uid' => null,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteUserKey($uid, $keyId, $encryptionModuleId) {
		try {
			$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, $uid);
			return !$this->view->file_exists($path) || $this->view->unlink($path);
		} catch (NoUserException $e) {
			// this exception can come from initMountPoints() from setupUserMounts()
			// for a deleted user.
			//
			// It means, that:
			// - we are not running in alternative storage mode because we don't call
			// initMountPoints() in that mode
			// - the keys were in the user's home but since the user was deleted, the
			// user's home is gone and so are the keys
			//
			// So there is nothing to do, just ignore.
		}
	}

	/**
	 * @inheritdoc
	 */
	public function deleteFileKey($path, $keyId, $encryptionModuleId) {
		$keyDir = $this->util->getFileKeyDir($encryptionModuleId, $path);
		return !$this->view->file_exists($keyDir . $keyId) || $this->view->unlink($keyDir . $keyId);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteAllFileKeys($path) {
		$keyDir = $this->util->getFileKeyDir('', $path);
		return !$this->view->file_exists($keyDir) || $this->view->deleteAll($keyDir);
	}

	/**
	 * @inheritdoc
	 */
	public function deleteSystemUserKey($keyId, $encryptionModuleId) {
		$path = $this->constructUserKeyPath($encryptionModuleId, $keyId, null);
		return !$this->view->file_exists($path) || $this->view->unlink($path);
	}

	/**
	 * construct path to users key
	 *
	 * @param string $encryptionModuleId
	 * @param string $keyId
	 * @param string $uid
	 * @return string
	 */
	protected function constructUserKeyPath($encryptionModuleId, $keyId, $uid) {
		if ($uid === null) {
			$path = $this->root_dir . '/' . $this->encryption_base_dir . '/' . $encryptionModuleId . '/' . $keyId;
		} else {
			$path = $this->root_dir . '/' . $uid . $this->encryption_base_dir . '/'
				. $encryptionModuleId . '/' . $uid . '.' . $keyId;
		}

		return Filesystem::normalizePath($path);
	}

	/**
	 * @param string $path
	 * @param string|null $uid
	 * @return string
	 * @throws ServerNotAvailableException
	 *
	 * Small helper function to fetch the key and verify the value for user and system keys
	 */
	private function getKeyWithUid(string $path, ?string $uid): string {
		$data = $this->getKey($path);

		if (!isset($data['key'])) {
			throw new ServerNotAvailableException('Key is invalid');
		}

		if ($data['key'] === '') {
			return '';
		}

		if (!array_key_exists('uid', $data) || $data['uid'] !== $uid) {
			// If the migration is done we error out
			$versionFromBeforeUpdate = $this->config->getSystemValueString('version', '0.0.0.0');
			if (version_compare($versionFromBeforeUpdate, '20.0.0.1', '<=')) {
				return $data['key'];
			}

			if ($this->config->getSystemValueBool('encryption.key_storage_migrated', true)) {
				throw new ServerNotAvailableException('Key has been modified');
			} else {
				//Otherwise we migrate
				$data['uid'] = $uid;
				$this->setKey($path, $data);
			}
		}

		return $data['key'];
	}

	/**
	 * Read the key from disk.
	 *
	 * @param string $path Path to key file.
	 * @return array Containing key as base64encoded key, and possibly the uid.
	 * @throws ServerNotAvailableException
	 */
	private function getKey($path): array {
		if (!$this->view->file_exists($path)) {
			return = [
				'key' => '',
			];
		}

		if (isset($this->keyCache[$path])) {
			return $this->keyCache[$path];
		}
			
		$data = $this->view->file_get_contents($path);
		$migrated = $this->config->getSystemValueBool('encryption.key_storage_migrated', true);

		if ($migrated) {
			try {
				$clearData = $this->crypto->decrypt($data);
				$dataArray = json_decode($clearData, true);
				if ($dataArray === null) {
					throw new ServerNotAvailableException('Invalid encryption key');
				}
				$key = $dataArray;
			} catch (\Exception $e) {
				// Config indicates migration completed, but decrypted data is invalid.
				throw new ServerNotAvailableException('Could not decrypt key', 0, $e);
			}
		} else {
			// If key storage migration isn't indicated as being complete,
			// attempt to decrypt the key as some may have already migrated.
			// Otherwise, fall back to returning the (non-migrated) base64-encoded key.
			try {
				$clearData = $this->crypto->decrypt($data);
				$dataArray = json_decode($clearData, true);
				if ($dataArray === null) {
					// used only to trigger fallback
					throw new ServerNotAvailableException('Invalid encryption key');
				}
				$key = $dataArray;
			} catch (\Throwable $e) {
				// Fallback: base64-encoded blob if decryption fails
				$key = [
					'key' => base64_encode($data)
				];
			}
		}

		$this->keyCache[$path] = $key;
		return $key;
	}

	/**
	 * Write the given key to disk.
	 *
	 * @param string $path Destination file path for the key
	 * @param array $key Associative array with encryption key data (must have 'key')	 
	 * @return bool True if key is persisted; throws on error.
	 * @throws \UnexpectedValueException if $key structure is invalid (rare)
	 * @throws \RuntimeException if encode, encrypt, or write fails or is incomplete
	 */
	private function setKey(string $path, array $key): bool {
		$this->keySetPreparation(dirname($path));

		if (!isset($key['key'])) {
			throw new \UnexpectedValueException('Provided $key missing required "key" entry');
		}

		try {
			$json = json_encode($key, JSON_THROW_ON_ERROR);
			$data = $this->crypto->encrypt($json);
		} catch (\JsonException $e) {
			throw new \RuntimeException('Failed to JSON encode key for storage: ' . $e->getMessage(), 0, $e);
		} catch (\Throwable $e) {
			throw new \RuntimeException('Failed to encrypt key for storage: ' . $e->getMessage(), 0, $e);
		}

		$result = $this->view->file_put_contents($path, $data);
		$expected = \strlen($data);
		if ($result === false || $result !== $expected) {
			throw new \RuntimeException(
				"Failed to write encryption key to {$path}: "
				. ($result === false ? 'file_put_contents returned false' : "wrote {$result} bytes, expected ".\strlen($data))
			);
		}

		$this->keyCache[$path] = $key;
		return true;
	}

	/**
	 * move keys if a file was renamed
	 *
	 * @param string $source
	 * @param string $target
	 * @return boolean
	 */
	public function renameKeys($source, $target) {
		$sourcePath = $this->getPathToKeys($source);
		$targetPath = $this->getPathToKeys($target);

		if ($this->view->file_exists($sourcePath)) {
			$this->keySetPreparation(dirname($targetPath));
			$this->view->rename($sourcePath, $targetPath);

			return true;
		}

		return false;
	}


	/**
	 * copy keys if a file was renamed
	 *
	 * @param string $source
	 * @param string $target
	 * @return boolean
	 */
	public function copyKeys($source, $target) {
		$sourcePath = $this->getPathToKeys($source);
		$targetPath = $this->getPathToKeys($target);

		if ($this->view->file_exists($sourcePath)) {
			$this->keySetPreparation(dirname($targetPath));
			$this->view->copy($sourcePath, $targetPath);
			return true;
		}

		return false;
	}

	/**
	 * backup keys of a given encryption module
	 *
	 * @param string $encryptionModuleId
	 * @param string $purpose
	 * @param string $uid
	 * @return bool
	 * @since 12.0.0
	 */
	public function backupUserKeys($encryptionModuleId, $purpose, $uid) {
		$source = $uid . $this->encryption_base_dir . '/' . $encryptionModuleId;
		$backupDir = $uid . $this->backup_base_dir;
		if (!$this->view->file_exists($backupDir)) {
			$this->view->mkdir($backupDir);
		}

		$backupDir = $backupDir . '/' . $purpose . '.' . $encryptionModuleId . '.' . $this->getTimestamp();
		$this->view->mkdir($backupDir);

		return $this->view->copy($source, $backupDir);
	}

	/**
	 * get the current timestamp
	 *
	 * @return int
	 */
	protected function getTimestamp() {
		return time();
	}

	/**
	 * get system wide path and detect mount points
	 *
	 * @param string $path
	 * @return string
	 */
	protected function getPathToKeys($path) {
		[$owner, $relativePath] = $this->util->getUidAndFilename($path);
		$systemWideMountPoint = $this->util->isSystemWideMountPoint($relativePath, $owner);

		if ($systemWideMountPoint) {
			$systemPath = $this->root_dir . '/' . $this->keys_base_dir . $relativePath . '/';
		} else {
			$systemPath = $this->root_dir . '/' . $owner . $this->keys_base_dir . $relativePath . '/';
		}

		return  Filesystem::normalizePath($systemPath, false);
	}

	/**
	 * Make preparations to filesystem for saving a key file
	 *
	 * @param string $path relative to the views root
	 */
	protected function keySetPreparation($path) {
		// If the file resides within a subdirectory, create it
		if (!$this->view->file_exists($path)) {
			$sub_dirs = explode('/', ltrim($path, '/'));
			$dir = '';
			foreach ($sub_dirs as $sub_dir) {
				$dir .= '/' . $sub_dir;
				if (!$this->view->is_dir($dir)) {
					$this->view->mkdir($dir);
				}
			}
		}
	}
}
