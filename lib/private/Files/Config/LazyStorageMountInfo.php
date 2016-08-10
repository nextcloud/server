<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Files\Config;

use OC\Files\Filesystem;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\IUser;

class LazyStorageMountInfo extends CachedMountInfo {
	/** @var IMountPoint */
	private $mount;

	/**
	 * CachedMountInfo constructor.
	 *
	 * @param IUser $user
	 * @param IMountPoint $mount
	 */
	public function __construct(IUser $user, IMountPoint $mount) {
		$this->user = $user;
		$this->mount = $mount;
	}

	/**
	 * @return int the numeric storage id of the mount
	 */
	public function getStorageId() {
		if (!$this->storageId) {
			if (method_exists($this->mount, 'getStorageNumericId')) {
				$this->storageId = $this->mount->getStorageNumericId();
			} else {
				$storage = $this->mount->getStorage();
				if (!$storage) {
					return -1;
				}
				$this->storageId = $storage->getStorageCache()->getNumericId();
			}
		}
		return parent::getStorageId();
	}

	/**
	 * @return int the fileid of the root of the mount
	 */
	public function getRootId() {
		if (!$this->rootId) {
			$this->rootId = $this->mount->getStorageRootId();
		}
		return parent::getRootId();
	}

	/**
	 * @return string the mount point of the mount for the user
	 */
	public function getMountPoint() {
		if (!$this->mountPoint) {
			$this->mountPoint = $this->mount->getMountPoint();
		}
		return parent::getMountPoint();
	}
}
