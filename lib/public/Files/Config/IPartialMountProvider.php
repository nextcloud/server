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
 * This interface marks mount providers that can provide IMountPoints related to
 * a path based on the provided mount and root metadata.
 *
 * @since 33.0.0
 */
interface IPartialMountProvider extends IMountProvider {

	/**
	 * Called during the Filesystem setup of a specific path.
	 *
	 * The provided arguments give information about the path being set up,
	 * as well as information about mount points known to be provided by the
	 * mount provider and contained in the path or in its sub-paths.
	 *
	 * Implementations should verify the MountProviderArgs and return the
	 * corresponding IMountPoint instances.
	 *
	 * @param string $path path for which the mounts are set up
	 * @param bool $forChildren when true, only child mounts for path should be returned
	 * @param MountProviderArgs[] $mountProviderArgs
	 * @param IStorageFactory $loader
	 * @return array<string, IMountPoint> IMountPoint instances, indexed by
	 *                                    mount-point
	 */
	public function getMountsForPath(
		string $path,
		bool $forChildren,
		array $mountProviderArgs,
		IStorageFactory $loader,
	): array;
}
