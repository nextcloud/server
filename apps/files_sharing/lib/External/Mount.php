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
use OC\Files\Storage\StorageFactory;
use OCA\Files_Sharing\ISharedMountPoint;
use Override;

class Mount extends MountPoint implements MoveableMount, ISharedMountPoint {
	public function __construct(
		string|Storage $storage,
		string $mountpoint,
		array $options,
		protected Manager $manager,
		?StorageFactory $loader = null,
	) {
		parent::__construct($storage, $mountpoint, $options, $loader, null, null, MountProvider::class);
	}

	/**
	 * Move the mount point to $target
	 *
	 * @param string $target the target mount point
	 */
	public function moveMount($target): bool {
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

	#[Override]
	public function getMountType(): string {
		return 'shared';
	}
}
