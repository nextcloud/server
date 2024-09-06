<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Config;

use OC\Files\Filesystem;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Node;
use OCP\IUser;

class CachedMountInfo implements ICachedMountInfo {
	protected IUser $user;
	protected int $storageId;
	protected int $rootId;
	protected string $mountPoint;
	protected ?int $mountId;
	protected string $rootInternalPath;
	protected string $mountProvider;
	protected string $key;

	/**
	 * CachedMountInfo constructor.
	 *
	 * @param IUser $user
	 * @param int $storageId
	 * @param int $rootId
	 * @param string $mountPoint
	 * @param int|null $mountId
	 * @param string $rootInternalPath
	 */
	public function __construct(
		IUser $user,
		int $storageId,
		int $rootId,
		string $mountPoint,
		string $mountProvider,
		?int $mountId = null,
		string $rootInternalPath = ''
	) {
		$this->user = $user;
		$this->storageId = $storageId;
		$this->rootId = $rootId;
		$this->mountPoint = $mountPoint;
		$this->mountId = $mountId;
		$this->rootInternalPath = $rootInternalPath;
		if (strlen($mountProvider) > 128) {
			throw new \Exception("Mount provider $mountProvider name exceeds the limit of 128 characters");
		}
		$this->mountProvider = $mountProvider;
		$this->key = $rootId . '::' . $mountPoint;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function getStorageId(): int {
		return $this->storageId;
	}

	public function getRootId(): int {
		return $this->rootId;
	}

	public function getMountPointNode(): ?Node {
		// TODO injection etc
		Filesystem::initMountPoints($this->getUser()->getUID());
		$userNode = \OC::$server->getUserFolder($this->getUser()->getUID());
		return $userNode->getParent()->getFirstNodeById($this->getRootId());
	}

	public function getMountPoint(): string {
		return $this->mountPoint;
	}

	public function getMountId(): ?int {
		return $this->mountId;
	}

	public function getRootInternalPath(): string {
		return $this->rootInternalPath;
	}

	public function getMountProvider(): string {
		return $this->mountProvider;
	}

	public function getKey(): string {
		return $this->key;
	}
}
