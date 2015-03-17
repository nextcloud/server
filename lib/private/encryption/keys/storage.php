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

namespace OC\Encryption\Keys;

use OC\Encryption\Util;
use OC\Files\View;
use OCA\Files_Encryption\Exception\EncryptionException;

class Storage implements \OCP\Encryption\Keys\IStorage {

	/** @var View */
	private $view;

	/** @var Util */
	private $util;

	// base dir where all the file related keys are stored
	private $keys_base_dir;
	private $encryption_base_dir;

	private $keyCache = array();

	/** @var string */
	private $encryptionModuleId;

	/**
	 * @param string $encryptionModuleId
	 * @param View $view
	 * @param Util $util
	 */
	public function __construct($encryptionModuleId, View $view, Util $util) {
		$this->view = $view;
		$this->util = $util;
		$this->encryptionModuleId = $encryptionModuleId;

		$this->encryption_base_dir = '/files_encryption';
		$this->keys_base_dir = $this->encryption_base_dir .'/keys';
	}

	/**
	 * get user specific key
	 *
	 * @param string $uid ID if the user for whom we want the key
	 * @param string $keyId id of the key
	 *
	 * @return mixed key
	 */
	public function getUserKey($uid, $keyId) {
		$path = '/' . $uid . $this->encryption_base_dir . '/'
			. $this->encryptionModuleId . '/' . $uid . '.' . $keyId;
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
		$path = $this->encryption_base_dir . '/' . $this->encryptionModuleId . '/' . $keyId;
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
		$path = '/' . $uid . $this->encryption_base_dir . '/'
			. $this->encryptionModuleId . '/' . $uid . '.' . $keyId;
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
		$path = $this->encryption_base_dir . '/'
			. $this->encryptionModuleId . '/' . $keyId;
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

		if ($this->view->file_exists($path)) {
			if (isset($this->keyCache[$path])) {
				$key =  $this->keyCache[$path];
			} else {
				$key = $this->view->file_get_contents($path);
				$this->keyCache[$path] = $key;
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

		$result = $this->view->file_put_contents($path, $key);

		if (is_int($result) && $result > 0) {
			$this->keyCache[$path] = $key;
			return true;
		}

		return false;
	}

	/**
	 * get path to key folder for a given file
	 *
	 * @param string $path path to the file, relative to data/
	 * @return string
	 * @throws EncryptionException
	 * @internal param string $keyId
	 */
	private function getFileKeyDir($path) {

		if ($this->view->is_dir($path)) {
			throw new EncryptionException('file was expected but directory was given', EncryptionException::GENERIC);
		}

		list($owner, $filename) = $this->util->getUidAndFilename($path);
		$filename = $this->util->stripPartialFileExtension($filename);

		// in case of system wide mount points the keys are stored directly in the data directory
		if ($this->util->isSystemWideMountPoint($filename)) {
			$keyPath = $this->keys_base_dir . $filename . '/';
		} else {
			$keyPath = '/' . $owner . $this->keys_base_dir . $filename . '/';
		}

		return \OC\Files\Filesystem::normalizePath($keyPath . $this->encryptionModuleId . '/', false);
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
