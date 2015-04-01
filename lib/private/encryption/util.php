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

use OC\Encryption\Exceptions\EncryptionHeaderKeyExistsException;
use OC\Encryption\Exceptions\EncryptionHeaderToLargeException;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\IConfig;

class Util {

	const HEADER_START = 'HBEGIN';
	const HEADER_END = 'HEND';
	const HEADER_PADDING_CHAR = '-';

	const HEADER_ENCRYPTION_MODULE_KEY = 'oc_encryption_module';

	/**
	 * block size will always be 8192 for a PHP stream
	 * @see https://bugs.php.net/bug.php?id=21641
	 * @var integer
	 */
	protected $headerSize = 8192;

	/**
	 * block size will always be 8192 for a PHP stream
	 * @see https://bugs.php.net/bug.php?id=21641
	 * @var integer
	 */
	protected $blockSize = 8192;

	/** @var View */
	protected $view;

	/** @var array */
	protected $ocHeaderKeys;

	/** @var Manager */
	protected $userManager;

	/** @var IConfig */
	protected $config;

	/** @var array paths excluded from encryption */
	protected $excludedPaths;

	/**
	 *
	 * @param \OC\Files\View $view
	 * @param \OC\User\Manager $userManager
	 * @param IConfig $config
	 */
	public function __construct(
		\OC\Files\View $view,
		\OC\User\Manager $userManager,
		IConfig $config) {

		$this->ocHeaderKeys = [
			self::HEADER_ENCRYPTION_MODULE_KEY
		];

		$this->view = $view;
		$this->userManager = $userManager;
		$this->config = $config;

		$this->excludedPaths[] = 'files_encryption';
	}

	/**
	 * read encryption module ID from header
	 *
	 * @param array $header
	 * @return string
	 */
	public function getEncryptionModuleId(array $header = null) {
		$id = '';
		$encryptionModuleKey = self::HEADER_ENCRYPTION_MODULE_KEY;

		if (isset($header[$encryptionModuleKey])) {
			$id = $header[$encryptionModuleKey];
		}

		return $id;
	}

	/**
	 * read header into array
	 *
	 * @param string $header
	 * @return array
	 */
	public function readHeader($header) {

		$result = array();

		if (substr($header, 0, strlen(self::HEADER_START)) === self::HEADER_START) {
			$endAt = strpos($header, self::HEADER_END);
			if ($endAt !== false) {
				$header = substr($header, 0, $endAt + strlen(self::HEADER_END));

				// +1 to not start with an ':' which would result in empty element at the beginning
				$exploded = explode(':', substr($header, strlen(self::HEADER_START)+1));

				$element = array_shift($exploded);
				while ($element !== self::HEADER_END) {
					$result[$element] = array_shift($exploded);
					$element = array_shift($exploded);
				}
			}
		}

		return $result;
	}

	/**
	 * create header for encrypted file
	 *
	 * @param array $headerData
	 * @param IEncryptionModule $encryptionModule
	 * @return string
	 * @throws EncryptionHeaderToLargeException if header has to many arguments
	 * @throws EncryptionHeaderKeyExistsException if header key is already in use
	 */
	public function createHeader(array $headerData, IEncryptionModule $encryptionModule) {
		$header = self::HEADER_START . ':' . self::HEADER_ENCRYPTION_MODULE_KEY . ':' . $encryptionModule->getId() . ':';
		foreach ($headerData as $key => $value) {
			if (in_array($key, $this->ocHeaderKeys)) {
				throw new EncryptionHeaderKeyExistsException('header key "'. $key . '" already reserved by ownCloud');
			}
			$header .= $key . ':' . $value . ':';
		}
		$header .= self::HEADER_END;

		if (strlen($header) > $this->getHeaderSize()) {
			throw new EncryptionHeaderToLargeException('max header size exceeded');
		}

		$paddedHeader = str_pad($header, $this->headerSize, self::HEADER_PADDING_CHAR, STR_PAD_RIGHT);

		return $paddedHeader;
	}

	/**
	 * go recursively through a dir and collect all files and sub files.
	 *
	 * @param string $dir relative to the users files folder
	 * @param string $mountPoint
	 * @return array with list of files relative to the users files folder
	 */
	public function getAllFiles($dir, $mountPoint = '') {
		$result = array();
		$dirList = array($dir);

		while ($dirList) {
			$dir = array_pop($dirList);
			$content = $this->view->getDirectoryContent($dir);

			foreach ($content as $c) {
				// getDirectoryContent() returns the paths relative to the mount points, so we need
				// to re-construct the complete path
				$path = ($mountPoint !== '') ? $mountPoint . '/' .  $c['path'] : $c['path'];
				if ($c['type'] === 'dir') {
					$dirList[] = $path;
				} else {
					$result[] = $path;
				}
			}

		}

		return $result;
	}

	/**
	 * check if it is a file uploaded by the user stored in data/user/files
	 * or a metadata file
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function isFile($path) {
		if (substr($path, 0, strlen('/files/')) === '/files/') {
			return true;
		}
		return false;
	}

	/**
	 * return size of encryption header
	 *
	 * @return integer
	 */
	public function getHeaderSize() {
		return $this->headerSize;
	}

	/**
	 * return size of block read by a PHP stream
	 *
	 * @return integer
	 */
	public function getBlockSize() {
		return $this->blockSize;
	}

	/**
	 * get the owner and the path for the file relative to the owners files folder
	 *
	 * @param string $path
	 * @return array
	 * @throws \BadMethodCallException
	 */
	public function getUidAndFilename($path) {

		$parts = explode('/', $path);
		$uid = '';
		if (count($parts) > 2) {
			$uid = $parts[1];
		}
		if (!$this->userManager->userExists($uid)) {
			throw new \BadMethodCallException(
				'path needs to be relative to the system wide data folder and point to a user specific file'
			);
		}

		$ownerPath = implode('/', array_slice($parts, 2));

		return array($uid, \OC\Files\Filesystem::normalizePath($ownerPath));

	}

	/**
	 * Remove .path extension from a file path
	 * @param string $path Path that may identify a .part file
	 * @return string File path without .part extension
	 * @note this is needed for reusing keys
	 */
	public function stripPartialFileExtension($path) {
		$extension = pathinfo($path, PATHINFO_EXTENSION);

		if ( $extension === 'part') {

			$newLength = strlen($path) - 5; // 5 = strlen(".part")
			$fPath = substr($path, 0, $newLength);

			// if path also contains a transaction id, we remove it too
			$extension = pathinfo($fPath, PATHINFO_EXTENSION);
			if(substr($extension, 0, 12) === 'ocTransferId') { // 12 = strlen("ocTransferId")
				$newLength = strlen($fPath) - strlen($extension) -1;
				$fPath = substr($fPath, 0, $newLength);
			}
			return $fPath;

		} else {
			return $path;
		}
	}

	public function getUserWithAccessToMountPoint($users, $groups) {
		$result = array();
		if (in_array('all', $users)) {
			$result = \OCP\User::getUsers();
		} else {
			$result = array_merge($result, $users);
			foreach ($groups as $group) {
				$result = array_merge($result, \OC_Group::usersInGroup($group));
			}
		}

		return $result;
	}

	/**
	 * check if the file is stored on a system wide mount point
	 * @param string $path relative to /data/user with leading '/'
	 * @return boolean
	 */
	public function isSystemWideMountPoint($path) {
		$normalizedPath = ltrim($path, '/');
		if (\OCP\App::isEnabled("files_external")) {
			$mounts = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mounts as $mount) {
				if ($mount['mountpoint'] == substr($normalizedPath, 0, strlen($mount['mountpoint']))) {
					if ($this->isMountPointApplicableToUser($mount)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * check if it is a path which is excluded by ownCloud from encryption
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function isExcluded($path) {
		$normalizedPath = \OC\Files\Filesystem::normalizePath($path);
		$root = explode('/', $normalizedPath, 4);
		if (count($root) > 2) {

			//detect system wide folders
			if (in_array($root[1], $this->excludedPaths)) {
				return true;
			}

			// detect user specific folders
			if ($this->userManager->userExists($root[1])
				&& in_array($root[2], $this->excludedPaths)) {

				return true;
			}
		}
		return false;
	}

	/**
	 * check if recovery key is enabled for user
	 *
	 * @param string $uid
	 * @return boolean
	 */
	public function recoveryEnabled($uid) {
		$enabled = $this->config->getUserValue($uid, 'encryption', 'recovery_enabled', '0');

		return ($enabled === '1') ? true : false;
	}

}
