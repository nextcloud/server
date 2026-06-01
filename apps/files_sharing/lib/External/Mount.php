<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\External;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\Storage;
use OC\Files\Storage\StorageFactory;
use OCA\Files_Sharing\ISharedMountPoint;
use OCP\Files\Mount\IMovableMount;
use Override;

class Mount extends MountPoint implements IMovableMount, ISharedMountPoint {
	public function __construct(
		string|Storage $storage,
		string $mountpoint,
		array $options,
		protected Manager $manager,
		?StorageFactory $loader = null,
	) {
		parent::__construct($storage, $mountpoint, $options, $loader, null, null, MountProvider::class);
	}

	#[Override]
	public function moveMount(string $target): bool {
		$result = $this->manager->setMountPoint($this->mountPoint, $target);
		$this->setMountPoint($target);

		return $result;
	}

	#[Override]
	public function removeMount(): bool {
		return $this->manager->removeShare($this->mountPoint);
	}

	#[Override]
	public function getMountType(): string {
		return 'shared';
	}
}
