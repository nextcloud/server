<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;
use OC\Files\Storage\Storage;
use OCA\Files_Sharing\ISharedMountPoint;

class Mount extends MountPoint implements MoveableMount, ISharedMountPoint {

	/**
	 * @param string|Storage $storage
	 * @param string $mountpoint
	 * @param array $options
	 * @param \OCA\Files_Sharing\External\Manager $manager
	 * @param \OC\Files\Storage\StorageFactory $loader
	 */
	public function __construct(
		$storage,
		$mountpoint,
		$options,
		protected $manager,
		$loader = null,
	) {
		parent::__construct($storage, $mountpoint, $options, $loader, null, null, MountProvider::class);
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 * @return bool
	 */
	public function moveMount($target) {
		$result = $this->manager->setMountPoint($this->mountPoint, $target);
		$this->setMountPoint($target);

		return $result;
	}

	/**
	 * Remove the mount points
	 */
	public function removeMount(): bool {
		return $this->manager->removeShare($this->mountPoint);
	}

	/**
	 * Get the type of mount point, used to distinguish things like shares and external storage
	 * in the web interface
	 *
	 * @return string
	 */
	public function getMountType() {
		return 'shared';
	}
}
