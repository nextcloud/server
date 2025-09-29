<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OC\Files\Mount\MoveableMount;
use OCA\Files_External\Config\ExternalMountPoint;
use OCA\Files_External\Service\UserStoragesService;
use OCP\Files\Storage\IStorage;
use OCP\Files\Storage\IStorageFactory;

/**
 * Person mount points can be moved by the user
 */
class PersonalMount extends ExternalMountPoint implements MoveableMount {
	/**
	 * @param UserStoragesService $storagesService
	 * @param int $storageId
	 * @param IStorage $storage
	 * @param string $mountpoint
	 * @param array $arguments (optional) configuration for the storage backend
	 * @param IStorageFactory $loader
	 * @param array $mountOptions mount specific options
	 * @param int $externalStorageId
	 */
	public function __construct(
		protected UserStoragesService $storagesService,
		StorageConfig $storageConfig,
		/** @var int id of the external storage (mount) (not the numeric id of the resulting storage!) */
		protected $numericExternalStorageId,
		$storage,
		$mountpoint,
		$arguments = null,
		$loader = null,
		$mountOptions = null,
		$mountId = null,
	) {
		parent::__construct($storageConfig, $storage, $mountpoint, $arguments, $loader, $mountOptions, $mountId);
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$storage = $this->storagesService->getStorage($this->numericExternalStorageId);
		// remove "/$user/files" prefix
		$targetParts = explode('/', trim($target, '/'), 3);
		$storage->setMountPoint($targetParts[2]);
		$this->storagesService->updateStorage($storage);
		$this->setMountPoint($target);
		return true;
	}

	/**
	 * Remove the mount points
	 *
	 * @return bool
	 */
	public function removeMount() {
		$this->storagesService->removeStorage($this->numericExternalStorageId);
		return true;
	}
}
