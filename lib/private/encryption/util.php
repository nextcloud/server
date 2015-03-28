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

use OC\Encryption\Exceptions\EncryptionHeaderToLargeException;
use OC\Encryption\Exceptions\EncryptionHeaderKeyExistsException;
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

	/** @var \OC\Files\View */
	protected $view;

	/** @var array */
	protected $ocHeaderKeys;

	/** @var \OC\User\Manager */
	protected $userManager;

	/** @var IConfig */
	protected $config;

	/** @var array paths excluded from encryption */
	protected $excludedPaths;

	/**
	 * @param \OC\Files\View $view root view
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
	public function getEncryptionModuleId(array $header) {
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
			throw new EncryptionHeaderToLargeException('max header size exceeded', EncryptionException::ENCRYPTION_HEADER_TO_LARGE);
		}

		$paddedHeader = str_pad($header, $this->headerSize, self::HEADER_PADDING_CHAR, STR_PAD_RIGHT);

		return $paddedHeader;
	}

	/**
	 * Find, sanitise and format users sharing a file
	 * @note This wraps other methods into a portable bundle
	 * @param string $path path relative to current users files folder
	 * @return array
	 */
	public function getSharingUsersArray($path) {

		// Make sure that a share key is generated for the owner too
		list($owner, $ownerPath) = $this->getUidAndFilename($path);

		// always add owner to the list of users with access to the file
		$userIds = array($owner);

		if (!$this->isFile($ownerPath)) {
			return array('users' => $userIds, 'public' => false);
		}

		$ownerPath = substr($ownerPath, strlen('/files'));
		$ownerPath = $this->stripPartialFileExtension($ownerPath);

		// Find out who, if anyone, is sharing the file
		$result = \OCP\Share::getUsersSharingFile($ownerPath, $owner);
		$userIds = \array_merge($userIds, $result['users']);
		$public = $result['public'] || $result['remote'];

		// check if it is a group mount
		if (\OCP\App::isEnabled("files_external")) {
			$mounts = \OC_Mount_Config::getSystemMountPoints();
			foreach ($mounts as $mount) {
				if ($mount['mountpoint'] == substr($ownerPath, 1, strlen($mount['mountpoint']))) {
					$mountedFor = $this->getUserWithAccessToMountPoint($mount['applicable']['users'], $mount['applicable']['groups']);
					$userIds = array_merge($userIds, $mountedFor);
				}
			}
		}

		// Remove duplicate UIDs
		$uniqueUserIds = array_unique($userIds);

		return array('users' => $uniqueUserIds, 'public' => $public);
	}

	/**
	 * go recursively through a dir and collect all files and sub files.
	 *
	 * @param string $dir relative to the users files folder
	 * @param strinf $mountPoint
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
	protected function isFile($path) {
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
	 * get the owner and the path for the owner
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
			throw new \BadMethodCallException('path needs to be relative to the system wide data folder and point to a user specific file');
		}

		$pathinfo = pathinfo($path);
		$partfile = false;
		$parentFolder = false;
		if (array_key_exists('extension', $pathinfo) && $pathinfo['extension'] === 'part') {
			// if the real file exists we check this file
			$filePath = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
			if ($this->view->file_exists($filePath)) {
				$pathToCheck = $pathinfo['dirname'] . '/' . $pathinfo['filename'];
			} else { // otherwise we look for the parent
				$pathToCheck = $pathinfo['dirname'];
				$parentFolder = true;
			}
			$partfile = true;
		} else {
			$pathToCheck = $path;
		}

		$pathToCheck = substr($pathToCheck, strlen('/' . $uid));

		$this->view->chroot('/' . $uid);
		$owner = $this->view->getOwner($pathToCheck);

		// Check that UID is valid
		if (!$this->userManager->userExists($owner)) {
				throw new \BadMethodCallException('path needs to be relative to the system wide data folder and point to a user specific file');
		}

		\OC\Files\Filesystem::initMountPoints($owner);

		$info = $this->view->getFileInfo($pathToCheck);
		$this->view->chroot('/' . $owner);
		$ownerPath = $this->view->getPath($info->getId());
		$this->view->chroot('/');

		if ($parentFolder) {
			$ownerPath = $ownerPath . '/'. $pathinfo['filename'];
		}

		if ($partfile) {
			$ownerPath = $ownerPath . '.' . $pathinfo['extension'];
		}

		return array(
			$owner,
			\OC\Files\Filesystem::normalizePath($ownerPath)
		);
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

	protected function getUserWithAccessToMountPoint($users, $groups) {
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
