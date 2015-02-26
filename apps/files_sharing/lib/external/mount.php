<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\External;

use OC\Files\Mount\MountPoint;
use OC\Files\Mount\MoveableMount;

class Mount extends MountPoint implements MoveableMount {

	/**
	 * @var \OCA\Files_Sharing\External\Manager
	 */
	protected $manager;

	/**
	 * @param string|\OC\Files\Storage\Storage $storage
	 * @param string $mountpoint
	 * @param array $options
	 * @param \OCA\Files_Sharing\External\Manager $manager
	 * @param \OC\Files\Storage\StorageFactory $loader
	 */
	public function __construct($storage, $mountpoint, $options, $manager, $loader = null) {
		parent::__construct($storage, $mountpoint, $options, $loader);
		$this->manager = $manager;
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
	 *
	 * @return mixed
	 * @return bool
	 */
	public function removeMount() {
		return $this->manager->removeShare($this->mountPoint);
	}
}
