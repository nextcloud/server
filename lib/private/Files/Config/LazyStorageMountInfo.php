<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Config;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node;
use OCP\IUser;
use OCP\Server;

class LazyStorageMountInfo implements ICachedMountInfo {
	private ?string $key = null;
	private ?int $storageId = null;
	private ?int $rootId = null;
	private ?ICacheEntry $rootCacheEntry = null;

	public function __construct(
		private readonly IUser $user,
		private readonly IMountPoint $mount,
	) {
	}

	/**
	 * @return int the numeric storage id of the mount
	 */
	#[\Override]
	public function getStorageId(): int {
		if ($this->storageId === null) {
			$this->storageId = $this->mount->getNumericStorageId();
		}
		return $this->storageId;
	}

	/**
	 * @return int the fileid of the root of the mount
	 */
	#[\Override]
	public function getRootId(): int {
		if ($this->rootId === null) {
			$this->rootId = $this->mount->getStorageRootId();
		}
		return $this->rootId;
	}

	/**
	 * @return string the mount point of the mount for the user
	 */
	#[\Override]
	public function getMountPoint(): string {
		return $this->mount->getMountPoint();
	}

	#[\Override]
	public function getMountId(): ?int {
		return $this->mount->getMountId();
	}

	/**
	 * Get the internal path (within the storage) of the root of the mount
	 *
	 * @return string
	 */
	#[\Override]
	public function getRootInternalPath(): string {
		return $this->mount->getInternalPath($this->mount->getMountPoint());
	}

	#[\Override]
	public function getMountProvider(): string {
		return $this->mount->getMountProvider();
	}

	#[\Override]
	public function getKey(): string {
		if ($this->key === null) {
			$this->key = $this->getRootId() . '::' . $this->getMountPoint();
		}
		return $this->key;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getMountPointNode(): ?Node {
		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->getUser()->getUID());
		return $userFolder->getParent()->getFirstNodeById($this->getRootId());
	}

	public function getRootCacheEntry(): ICacheEntry {
		if ($this->rootCacheEntry === null) {
			$this->rootCacheEntry = $this->mount->getStorage()->getCache()->get();
		}
		return $this->rootCacheEntry;
	}


}
