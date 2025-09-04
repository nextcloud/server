<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorageFactory;

/**
 * This interface represents mounts that can return IMountPoint instances that
 * are related to the provided mount information and metadata.
 */
interface IPartialMountProvider extends IMountProvider {

	/**
	 * Given the path for which mounts need to be set up, and an array of
	 * IMountProviderArgs that provide information for the mount point and the
	 * root of the mount, implementations of this function should return
	 * IMountPoint instances after validating that the provided information
	 * is still accurate.
	 *
	 * @param string $path path for which the mounts are setup
	 * @param IMountProviderArgs[] $mountProviderArgs
	 * @param IStorageFactory $loader
	 * @return array<string, IMountPoint> IMountPoint instances, indexed by
	 *                                    mount-point
	 */
	public function getMountsFromMountPoints(
		string $path,
		array $mountProviderArgs,
		IStorageFactory $loader,
	): array;
}
