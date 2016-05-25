<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author scambra <sergio@entrecables.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

namespace OC\Files\Storage;

use OC\Files\Filesystem;
use OCA\Files_Sharing\ISharedStorage;
use OCA\Files_Sharing\Propagator;
use OCA\Files_Sharing\SharedMount;
use OCP\Lock\ILockingProvider;

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class Shared extends \OC\Files\Storage\Common implements ISharedStorage {

	private $share;   // the shared resource
	private $files = array();
	private static $isInitialized = array();
	private $local = null;

	/**
	 * @var \OC\Files\View
	 */
	private $ownerView;

	/**
	 * @var \OCA\Files_Sharing\Propagation\PropagationManager
	 */
	private $propagationManager;

	/**
	 * @var string
	 */
	private $user;

	private $initialized = false;

	public function __construct($arguments) {
		$this->share = $arguments['share'];
		$this->ownerView = $arguments['ownerView'];
		$this->propagationManager = $arguments['propagationManager'];
		$this->user = $arguments['user'];
	}

	private function init() {
		if ($this->initialized) {
			return;
		}
		$this->initialized = true;
		Filesystem::initMountPoints($this->share['uid_owner']);

		// for updating our etags when changes are made to the share from the owners side (probably indirectly by us trough another share)
		$this->propagationManager->listenToOwnerChanges($this->share['uid_owner'], $this->user);
	}

	/**
	 * get id of the mount point
	 *
	 * @return string
	 */
	public function getId() {
		return 'shared::' . $this->getMountPoint();
	}

	/**
	 * get file cache of the shared item source
	 *
	 * @return int
	 */
	public function getSourceId() {
		return (int)$this->share['file_source'];
	}

	/**
	 * Get the source file path, permissions, and owner for a shared file
	 *
	 * @param string $target Shared target file path
	 * @return array Returns array with the keys path, permissions, and owner or false if not found
	 */
	public function getFile($target) {
		$this->init();
		if (!isset($this->files[$target])) {
			// Check for partial files
			if (pathinfo($target, PATHINFO_EXTENSION) === 'part') {
				$source = \OC_Share_Backend_File::getSource(substr($target, 0, -5), $this->getShare());
				if ($source) {
					$source['path'] .= '.part';
					// All partial files have delete permission
					$source['permissions'] |= \OCP\Constants::PERMISSION_DELETE;
				}
			} else {
				$source = \OC_Share_Backend_File::getSource($target, $this->getShare());
			}
			$this->files[$target] = $source;
		}
		return $this->files[$target];
	}

	/**
	 * Get the source file path for a shared file
	 *
	 * @param string $target Shared target file path
	 * @return string|false source file path or false if not found
	 */
	public function getSourcePath($target) {
		$source = $this->getFile($target);
		if ($source) {
			if (!isset($source['fullPath'])) {
				\OC\Files\Filesystem::initMountPoints($source['fileOwner']);
				$mount = \OC\Files\Filesystem::getMountByNumericId($source['storage']);
				if (is_array($mount) && !empty($mount)) {
					$this->files[$target]['fullPath'] = $mount[key($mount)]->getMountPoint() . $source['path'];
				} else {
					$this->files[$target]['fullPath'] = false;
					\OCP\Util::writeLog('files_sharing', "Unable to get mount for shared storage '" . $source['storage'] . "' user '" . $source['fileOwner'] . "'", \OCP\Util::ERROR);
				}
			}
			return $this->files[$target]['fullPath'];
		}
		return false;
	}

	/**
	 * Get the permissions granted for a shared file
	 *
	 * @param string $target Shared target file path
	 * @return int CRUDS permissions granted
	 */
	public function getPermissions($target = '') {
		$permissions = $this->share['permissions'];
		// part files and the mount point always have delete permissions
		if ($target === '' || pathinfo($target, PATHINFO_EXTENSION) === 'part') {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}

		if (\OCP\Util::isSharingDisabledForUser()) {
			$permissions &= ~\OCP\Constants::PERMISSION_SHARE;
		}

		return $permissions;
	}

	public function mkdir($path) {
		if ($path == '' || $path == '/' || !$this->isCreatable(dirname($path))) {
			return false;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->mkdir($internalPath);
		}
		return false;
	}

	/**
	 * Delete the directory if DELETE permission is granted
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function rmdir($path) {

		// never delete a share mount point
		if (empty($path)) {
			return false;
		}

		if (($source = $this->getSourcePath($path)) && $this->isDeletable($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->rmdir($internalPath);
		}
		return false;
	}

	public function opendir($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->opendir($internalPath);
	}

	public function is_dir($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->is_dir($internalPath);
	}

	public function is_file($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->is_file($internalPath);
		}
		return false;
	}

	public function stat($path) {
		if ($path == '' || $path == '/') {
			$stat['size'] = $this->filesize($path);
			$stat['mtime'] = $this->filemtime($path);
			return $stat;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->stat($internalPath);
		}
		return false;
	}

	public function filetype($path) {
		if ($path == '' || $path == '/') {
			return 'dir';
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->filetype($internalPath);
		}
		return false;
	}

	public function filesize($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->filesize($internalPath);
	}

	public function isCreatable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_CREATE);
	}

	public function isReadable($path) {
		$isReadable = false;
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			$isReadable = $storage->isReadable($internalPath);
		}

		return $isReadable && $this->file_exists($path);
	}

	public function isUpdatable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_UPDATE);
	}

	public function isDeletable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_DELETE);
	}

	public function isSharable($path) {
		if (\OCP\Util::isSharingDisabledForUser() || !\OC\Share\Share::isResharingAllowed()) {
			return false;
		}
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_SHARE);
	}

	public function file_exists($path) {
		if ($path == '' || $path == '/') {
			return true;
		} else if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->file_exists($internalPath);
		}
		return false;
	}

	public function filemtime($path) {
		$source = $this->getSourcePath($path);
		list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
		return $storage->filemtime($internalPath);
	}

	public function file_get_contents($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			$info = array(
				'target' => $this->getMountPoint() . $path,
				'source' => $source,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_get_contents', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->file_get_contents($internalPath);
		}
	}

	public function file_put_contents($path, $data) {
		if ($source = $this->getSourcePath($path)) {
			// Check if permission is granted
			if (($this->file_exists($path) && !$this->isUpdatable($path))
				|| ($this->is_dir($path) && !$this->isCreatable($path))
			) {
				return false;
			}
			$info = array(
				'target' => $this->getMountPoint() . '/' . $path,
				'source' => $source,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_put_contents', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			$result = $storage->file_put_contents($internalPath, $data);
			return $result;
		}
		return false;
	}

	/**
	 * Delete the file if DELETE permission is granted
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function unlink($path) {

		// never delete a share mount point
		if (empty($path)) {
			return false;
		}
		if ($source = $this->getSourcePath($path)) {
			if ($this->isDeletable($path)) {
				list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
				return $storage->unlink($internalPath);
			}
		}
		return false;
	}

	public function rename($path1, $path2) {
		$this->init();
		// we need the paths relative to data/user/files
		$relPath1 = $this->getMountPoint() . '/' . $path1;
		$relPath2 = $this->getMountPoint() . '/' . $path2;
		$pathinfo = pathinfo($relPath1);

		$isPartFile = (isset($pathinfo['extension']) && $pathinfo['extension'] === 'part');
		$targetExists = $this->file_exists($path2);
		$sameFolder = (dirname($relPath1) === dirname($relPath2));
		if ($targetExists || ($sameFolder && !$isPartFile)) {
			// note that renaming a share mount point is always allowed
			if (!$this->isUpdatable('')) {
				return false;
			}
		} else {
			if (!$this->isCreatable('')) {
				return false;
			}
		}


		/**
		 * @var \OC\Files\Storage\Storage $sourceStorage
		 */
		list($sourceStorage, $sourceInternalPath) = $this->resolvePath($path1);
		/**
		 * @var \OC\Files\Storage\Storage $targetStorage
		 */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($path2);

		return $targetStorage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	public function copy($path1, $path2) {
		// Copy the file if CREATE permission is granted
		if ($this->isCreatable(dirname($path2))) {
			/**
			 * @var \OC\Files\Storage\Storage $sourceStorage
			 */
			list($sourceStorage, $sourceInternalPath) = $this->resolvePath($path1);
			/**
			 * @var \OC\Files\Storage\Storage $targetStorage
			 */
			list($targetStorage, $targetInternalPath) = $this->resolvePath($path2);

			return $targetStorage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
		return false;
	}

	public function fopen($path, $mode) {
		if ($source = $this->getSourcePath($path)) {
			switch ($mode) {
				case 'r+':
				case 'rb+':
				case 'w+':
				case 'wb+':
				case 'x+':
				case 'xb+':
				case 'a+':
				case 'ab+':
				case 'w':
				case 'wb':
				case 'x':
				case 'xb':
				case 'a':
				case 'ab':
					$creatable = $this->isCreatable($path);
					$updatable = $this->isUpdatable($path);
					// if neither permissions given, no need to continue
					if (!$creatable && !$updatable) {
						return false;
					}

					$exists = $this->file_exists($path);
					// if a file exists, updatable permissions are required
					if ($exists && !$updatable) {
						return false;
					}

					// part file is allowed if !$creatable but the final file is $updatable
					if (pathinfo($path, PATHINFO_EXTENSION) !== 'part') {
						if (!$exists && !$creatable) {
							return false;
						}
					}
			}
			$info = array(
				'target' => $this->getMountPoint() . $path,
				'source' => $source,
				'mode' => $mode,
			);
			\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'fopen', $info);
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->fopen($internalPath, $mode);
		}
		return false;
	}

	public function getMimeType($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getMimeType($internalPath);
		}
		return false;
	}

	public function free_space($path) {
		$source = $this->getSourcePath($path);
		if ($source) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->free_space($internalPath);
		}
		return \OCP\Files\FileInfo::SPACE_UNKNOWN;
	}

	public function getLocalFile($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getLocalFile($internalPath);
		}
		return false;
	}

	public function touch($path, $mtime = null) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->touch($internalPath, $mtime);
		}
		return false;
	}

	/**
	 * return mount point of share, relative to data/user/files
	 *
	 * @return string
	 */
	public function getMountPoint() {
		return $this->share['file_target'];
	}

	public function setMountPoint($path) {
		$this->share['file_target'] = $path;
	}

	public function getShareType() {
		return $this->share['share_type'];
	}

	/**
	 * does the group share already has a user specific unique name
	 *
	 * @return bool
	 */
	public function uniqueNameSet() {
		return (isset($this->share['unique_name']) && $this->share['unique_name']);
	}

	/**
	 * the share now uses a unique name of this user
	 *
	 * @brief the share now uses a unique name of this user
	 */
	public function setUniqueName() {
		$this->share['unique_name'] = true;
	}

	/**
	 * get share ID
	 *
	 * @return integer unique share ID
	 */
	public function getShareId() {
		return $this->share['id'];
	}

	/**
	 * get the user who shared the file
	 *
	 * @return string
	 */
	public function getSharedFrom() {
		return $this->share['uid_owner'];
	}

	/**
	 * @return array
	 */
	public function getShare() {
		return $this->share;
	}

	/**
	 * return share type, can be "file" or "folder"
	 *
	 * @return string
	 */
	public function getItemType() {
		return $this->share['item_type'];
	}

	public function hasUpdated($path, $time) {
		return $this->filemtime($path) > $time;
	}

	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\Shared_Cache($storage);
	}

	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\SharedScanner($storage);
	}

	public function getWatcher($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OC\Files\Cache\Shared_Watcher($storage);
	}

	public function getOwner($path) {
		if ($path == '') {
			$path = $this->getMountPoint();
		}
		$source = $this->getFile($path);
		if ($source) {
			return $source['fileOwner'];
		}
		return false;
	}

	public function getETag($path) {
		if ($source = $this->getSourcePath($path)) {
			list($storage, $internalPath) = \OC\Files\Filesystem::resolvePath($source);
			return $storage->getETag($internalPath);
		}
		return null;
	}

	/**
	 * unshare complete storage, also the grouped shares
	 *
	 * @return bool
	 */
	public function unshareStorage() {
		$result = true;
		if (!empty($this->share['grouped'])) {
			foreach ($this->share['grouped'] as $share) {
				$result = $result && \OCP\Share::unshareFromSelf($share['item_type'], $share['file_target']);
			}
		}
		$result = $result && \OCP\Share::unshareFromSelf($this->getItemType(), $this->getMountPoint());

		return $result;
	}

	/**
	 * Resolve the path for the source of the share
	 *
	 * @param string $path
	 * @return array
	 */
	private function resolvePath($path) {
		$source = $this->getSourcePath($path);
		return \OC\Files\Filesystem::resolvePath($source);
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		/** @var \OCP\Files\Storage $targetStorage */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($targetInternalPath);
		return $targetStorage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		/** @var \OCP\Files\Storage $targetStorage */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($targetInternalPath);
		return $targetStorage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * @param string $path
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 * @throws \OCP\Lock\LockedException
	 */
	public function acquireLock($path, $type, ILockingProvider $provider) {
		/** @var \OCP\Files\Storage $targetStorage */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($path);
		$targetStorage->acquireLock($targetInternalPath, $type, $provider);
		// lock the parent folders of the owner when locking the share as recipient
		if ($path === '') {
			$sourcePath = $this->ownerView->getPath($this->share['file_source']);
			$this->ownerView->lockFile(dirname($sourcePath), ILockingProvider::LOCK_SHARED, true);
		}
	}

	/**
	 * @param string $path
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 */
	public function releaseLock($path, $type, ILockingProvider $provider) {
		/** @var \OCP\Files\Storage $targetStorage */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($path);
		$targetStorage->releaseLock($targetInternalPath, $type, $provider);
		// unlock the parent folders of the owner when unlocking the share as recipient
		if ($path === '') {
			$sourcePath = $this->ownerView->getPath($this->share['file_source']);
			$this->ownerView->unlockFile(dirname($sourcePath), ILockingProvider::LOCK_SHARED, true);
		}
	}

	/**
	 * @param string $path
	 * @param int $type \OCP\Lock\ILockingProvider::LOCK_SHARED or \OCP\Lock\ILockingProvider::LOCK_EXCLUSIVE
	 * @param \OCP\Lock\ILockingProvider $provider
	 */
	public function changeLock($path, $type, ILockingProvider $provider) {
		/** @var \OCP\Files\Storage $targetStorage */
		list($targetStorage, $targetInternalPath) = $this->resolvePath($path);
		$targetStorage->changeLock($targetInternalPath, $type, $provider);
	}

	/**
	 * @return array [ available, last_checked ]
	 */
	public function getAvailability() {
		// shares do not participate in availability logic
		return [
			'available' => true,
			'last_checked' => 0
		];
	}

	/**
	 * @param bool $available
	 */
	public function setAvailability($available) {
		// shares do not participate in availability logic
	}

	public function isLocal() {
		if (!is_null($this->local)) {
			$this->init();
			$ownerPath = $this->ownerView->getPath($this->share['item_source']);
			list($targetStorage) = $this->ownerView->resolvePath($ownerPath);
			$this->local = $targetStorage->isLocal();
		}
		return $this->local;
	}
}
