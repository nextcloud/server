<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author scambra <sergio@entrecables.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
use OC\Files\Cache\FailedCache;
use OCA\Files_Sharing\ISharedStorage;
use OCP\Constants;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Storage\IStorage;
use OCP\Lock\ILockingProvider;
use OC\Files\Storage\FailedStorage;
use OCP\Files\NotFoundException;

/**
 * Convert target path to source path and pass the function call to the correct storage provider
 */
class Shared extends \OC\Files\Storage\Wrapper\Jail implements ISharedStorage {
	/** @var \OCP\Share\IShare */
	private $superShare;

	/** @var \OCP\Share\IShare[] */
	private $groupedShares;

	/**
	 * @var \OC\Files\View
	 */
	private $ownerView;

	private $initialized = false;

	/**
	 * @var ICacheEntry
	 */
	private $sourceRootInfo;

	/**
	 * @var IStorage
	 */
	private $sourceStorage;

	/** @var string */
	private $user;

	/**
	 * @var \OCP\ILogger
	 */
	private $logger;

	public function __construct($arguments) {
		$this->ownerView = $arguments['ownerView'];
		$this->logger = \OC::$server->getLogger();

		$this->superShare = $arguments['superShare'];
		$this->groupedShares = $arguments['groupedShares'];

		$this->user = $arguments['user'];

		parent::__construct([
			'storage' => null, // init later
			'root' => null, // init later
		]);
	}

	private function init() {
		if ($this->initialized) {
			return;
		}
		$this->initialized = true;
		try {
			Filesystem::initMountPoints($this->superShare->getShareOwner());
			$sourcePath = $this->ownerView->getPath($this->superShare->getNodeId(), false);
			list($this->sourceStorage, $sourceInternalPath) = $this->ownerView->resolvePath($sourcePath);
			$this->sourceRootInfo = $this->sourceStorage->getCache()->get($sourceInternalPath);
			// adjust jail
			$this->rootPath = $sourceInternalPath;

		} catch (\Exception $e) {
			$this->sourceStorage = new FailedStorage(['exception' => $e]);
			if (!$e instanceof NotFoundException) {
				$this->logger->logException($e);
			}
		}
		$this->storage = $this->sourceStorage;
	}

	/**
	 * @inheritdoc
	 */
	public function instanceOfStorage($class) {
		if (in_array($class, ['\OC\Files\Storage\Home', '\OC\Files\ObjectStore\HomeObjectStoreStorage'])) {
			return false;
		}
		return parent::instanceOfStorage($class);
	}

	/**
	 * @return string
	 */
	public function getShareId() {
		return $this->superShare->getId();
	}

	private function isValid() {
		$this->init();
		return $this->sourceRootInfo && ($this->sourceRootInfo->getPermissions() & Constants::PERMISSION_SHARE) === Constants::PERMISSION_SHARE;
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
	 * Get the permissions granted for a shared file
	 *
	 * @param string $target Shared target file path
	 * @return int CRUDS permissions granted
	 */
	public function getPermissions($target = '') {
		if (!$this->isValid()) {
			return 0;
		}
		$permissions = $this->superShare->getPermissions();
		// part files and the mount point always have delete permissions
		if ($target === '' || pathinfo($target, PATHINFO_EXTENSION) === 'part') {
			$permissions |= \OCP\Constants::PERMISSION_DELETE;
		}

		if (\OCP\Util::isSharingDisabledForUser()) {
			$permissions &= ~\OCP\Constants::PERMISSION_SHARE;
		}

		return $permissions;
	}

	public function isCreatable($path) {
		return ($this->getPermissions($path) & \OCP\Constants::PERMISSION_CREATE);
	}

	public function isReadable($path) {
		if (!$this->isValid()) {
			return false;
		}
		if (!$this->file_exists($path)) {
			return false;
		}
		list($storage, $internalPath) = $this->resolvePath($path);
		return $storage->isReadable($internalPath);
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
			return parent::fopen($path, $mode);
		}
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function rename($path1, $path2) {
		$isPartFile = pathinfo($path1, PATHINFO_EXTENSION) === 'part';
		$targetExists = $this->file_exists($path2);
		$sameFodler = dirname($path1) === dirname($path2);

		if ($targetExists || ($sameFodler && !$isPartFile)) {
			if (!$this->isUpdatable('')) {
				return false;
			}
		} else {
			if (!$this->isCreatable('')) {
				return false;
			}
		}

		return parent::rename($path1, $path2);
	}

	/**
	 * return mount point of share, relative to data/user/files
	 *
	 * @return string
	 */
	public function getMountPoint() {
		return $this->superShare->getTarget();
	}

	/**
	 * @param string $path
	 */
	public function setMountPoint($path) {
		$this->superShare->setTarget($path);

		foreach ($this->groupedShares as $share) {
			$share->setTarget($path);
		}
	}

	/**
	 * get the user who shared the file
	 *
	 * @return string
	 */
	public function getSharedFrom() {
		return $this->superShare->getShareOwner();
	}

	/**
	 * @return \OCP\Share\IShare
	 */
	public function getShare() {
		return $this->superShare;
	}

	/**
	 * return share type, can be "file" or "folder"
	 *
	 * @return string
	 */
	public function getItemType() {
		return $this->superShare->getNodeType();
	}

	public function getCache($path = '', $storage = null) {
		$this->init();
		if (is_null($this->sourceStorage) || $this->sourceStorage instanceof FailedStorage) {
			return new FailedCache(false);
		}
		if (!$storage) {
			$storage = $this;
		}
		return new \OCA\Files_Sharing\Cache($storage, $this->sourceStorage, $this->sourceRootInfo);
	}

	public function getScanner($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}
		return new \OCA\Files_Sharing\Scanner($storage);
	}

	public function getPropagator($storage = null) {
		if (isset($this->propagator)) {
			return $this->propagator;
		}

		if (!$storage) {
			$storage = $this;
		}
		$this->propagator = new \OCA\Files_Sharing\SharedPropagator($storage, \OC::$server->getDatabaseConnection());
		return $this->propagator;
	}

	public function getOwner($path) {
		return $this->superShare->getShareOwner();
	}

	/**
	 * unshare complete storage, also the grouped shares
	 *
	 * @return bool
	 */
	public function unshareStorage() {
		foreach ($this->groupedShares as $share) {
			\OC::$server->getShareManager()->deleteFromSelf($share, $this->user);
		}
		return true;
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
			$sourcePath = $this->ownerView->getPath($this->superShare->getNodeId());
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
			$sourcePath = $this->ownerView->getPath($this->superShare->getNodeId());
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

	public function getSourceStorage() {
		return $this->sourceStorage;
	}

	public function file_get_contents($path) {
		$info = [
			'target' => $this->getMountPoint() . '/' . $path,
			'source' => $this->getSourcePath($path),
		];
		\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_get_contents', $info);
		return parent::file_get_contents($path);
	}

	public function file_put_contents($path, $data) {
		$info = [
			'target' => $this->getMountPoint() . '/' . $path,
			'source' => $this->getSourcePath($path),
		];
		\OCP\Util::emitHook('\OC\Files\Storage\Shared', 'file_put_contents', $info);
		return parent::file_put_contents($path, $data);
	}

	public function getWrapperStorage() {
		$this->init();

		return $this->sourceStorage;
	}
}
