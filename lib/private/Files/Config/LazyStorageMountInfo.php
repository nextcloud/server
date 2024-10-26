<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Config;

use OCP\Files\Mount\IMountPoint;
use OCP\IUser;

class LazyStorageMountInfo extends CachedMountInfo {
	private IMountPoint $mount;

	/**
	 * CachedMountInfo constructor.
	 *
	 * @param IUser $user
	 * @param IMountPoint $mount
	 */
	public function __construct(IUser $user, IMountPoint $mount) {
		$this->user = $user;
		$this->mount = $mount;
		$this->rootId = 0;
		$this->storageId = 0;
		$this->mountPoint = '';
		$this->key = '';
	}

	/**
	 * @return int the numeric storage id of the mount
	 */
	public function getStorageId(): int {
		if (!$this->storageId) {
			$this->storageId = $this->mount->getNumericStorageId();
		}
		return parent::getStorageId();
	}

	/**
	 * @return int the fileid of the root of the mount
	 */
	public function getRootId(): int {
		if (!$this->rootId) {
			$this->rootId = $this->mount->getStorageRootId();
		}
		return parent::getRootId();
	}

	/**
	 * @return string the mount point of the mount for the user
	 */
	public function getMountPoint(): string {
		if (!$this->mountPoint) {
			$this->mountPoint = $this->mount->getMountPoint();
		}
		return parent::getMountPoint();
	}

	public function getMountId(): ?int {
		return $this->mount->getMountId();
	}

	/**
	 * Get the internal path (within the storage) of the root of the mount
	 *
	 * @return string
	 */
	public function getRootInternalPath(): string {
		return $this->mount->getInternalPath($this->mount->getMountPoint());
	}

	public function getMountProvider(): string {
		return $this->mount->getMountProvider();
	}

	public function getKey(): string {
		if (!$this->key) {
			$this->key = $this->getRootId() . '::' . $this->getMountPoint();
		}
		return $this->key;
	}
}
