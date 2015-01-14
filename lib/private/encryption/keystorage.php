<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\Encryption;

class KeyStorage implements \OCP\Encryption\IKeyStorage {

	/** @var \OC\Files\View */
	private $view;

	/** @var \OC\Encryption\Util */
	private $util;

	// base dir where all the file related keys are stored
	private static $keys_base_dir = '/files_encryption/keys/';
	private static $encryption_base_dir = '/files_encryption';

	private static $key_cache = array(); // cache keys

	/**
	 * @param \OC\Files\View $view
	 * @param \OC\Encryption\Util $util
	 */
	public function __construct(\OC\Files\View $view, \OC\Encryption\Util $util) {
		$this->view = $view;
		$this->util = $util;
	}

	/**
	 * get user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyid id of the key
	 *
	 * @return mixed key
	 */
	public function getUserKey($uid, $keyid) {
		$path = '/' . $uid . self::$encryption_base_dir . '/' . $uid . '.' . $keyid;
		return $this->getKey($path);

	}

	/**
	 * get file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getFileKey($path, $keyId) {
		$keyDir = $this->getFileKeyDir($path);
		return $this->getKey($keyDir . $keyId);
	}

	/**
	 * get system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getSystemUserKey($keyId) {
		$path = '/' . self::$encryption_base_dir . '/' . $keyId;
		return $this->getKey($path);
	}

	/**
	 * set user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyId id of the key
	 * @param mixed $key
	 */
	public function setUserKey($uid, $keyId, $key) {
		$path = '/' . $uid . self::$encryption_base_dir . '/' . $uid . '.' . $keyId;
		return $this->setKey($path, $key);
	}

	/**
	 * set file specific key
	 *
	 * @param string $path path to file
	 * @param string $keyId id of the key
	 * @param mixed $key
	 */
	public function setFileKey($path, $keyId, $key) {
		$keyDir = $this->getFileKeyDir($path);
		return $this->setKey($keyDir . $keyId, $key);
	}

	/**
	 * set system-wide encryption keys not related to a specific user,
	 * e.g something like a key for public link shares
	 *
	 * @param string $keyId id of the key
	 * @param mixed $key
	 *
	 * @return mixed key
	 */
	public function setSystemUserKey($keyId, $key) {
		$path = '/' . self::$encryption_base_dir . '/' . $keyId;
		return $this->setKey($path, $key);
	}


	/**
	 * read key from hard disk
	 *
	 * @param string $path to key
	 * @return string
	 */
	private function getKey($path) {

		$key = '';

		if (isset(self::$key_cache[$path])) {
			$key =  self::$key_cache[$path];
		} else {

			/** @var \OCP\Files\Storage $storage */
			list($storage, $internalPath) = $this->view->resolvePath($path);

			if ($storage->file_exists($internalPath)) {
				$key = $storage->file_get_contents($internalPath);
				self::$key_cache[$path] = $key;
			}

		}

		return $key;
	}

	/**
	 * write key to disk
	 *
	 *
	 * @param string $path path to key directory
	 * @param string $key key
	 * @return bool
	 */
	private function setKey($path, $key) {
		$this->keySetPreparation(dirname($path));

		/** @var \OCP\Files\Storage $storage */
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($path);
		$result = $storage->file_put_contents($internalPath, $key);

		if (is_int($result) && $result > 0) {
			self::$key_cache[$path] = $key;
			return true;
		}

		return false;
	}

	/**
	 * get path to key folder for a given file
	 *
	 * @param string $path path to the file, relative to the users file directory
	 * @return string
	 */
	private function getFileKeyDir($path) {

		if ($this->view->is_dir('/' . \OCP\User::getUser() . '/' . $path)) {
			throw new Exception\EncryptionException('file was expected but directoy was given', Exception\EncryptionException::GENERIC);
		}

		list($owner, $filename) = $this->util->getUidAndFilename($path);
		$filename = $this->util->stripPartialFileExtension($filename);
		$filePath_f = ltrim($filename, '/');

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($this->util->isSystemWideMountPoint($filename)) {
			$keyPath = self::$keys_base_dir . $filePath_f . '/';
		} else {
			$keyPath = '/' . $owner . self::$keys_base_dir . $filePath_f . '/';
		}

		return $keyPath;
	}

	/**
	 * Make preparations to filesystem for saving a keyfile
	 *
	 * @param string $path relative to the views root
	 */
	protected function keySetPreparation($path) {
		// If the file resides within a subdirectory, create it
		if (!$this->view->file_exists($path)) {
			$sub_dirs = explode('/', $path);
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
