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
	 * Get the mounts for a user by path.
	 *
	 * Called during the Filesystem setup of a specific path.
	 *
	 * The provided arguments give information about the path being set up,
	 * as well as information about mount points known to be provided by the
	 * mount provider and contained in the path or in its sub-paths.
	 *
	 * Implementations should verify the MountProviderArgs and return the
	 * corresponding IMountPoint instances.
	 *
	 * If the mount for one of the MountProviderArgs no longer exists, implementations
	 * should simply leave them out from the returned mounts.
	 *
	 * Implementations are allowed to, but not expected to, return more mounts than requested.
	 *
	 * The user for which the mounts are being setup can be found in the `mountInfo->getUser()`
	 * of a MountProviderArgs.
	 * All provided MountProviderArgs will always be for the same user.
	 *
	 * @param string $setupPathHint path for which the mounts are being set up.
	 *                              This might not be the same as the path of the expected mount(s).
	 * @param bool $forChildren when true, only child mounts for `$setupPathHint` were requested.
	 *                          The $mountProviderArgs will hold a list of expected child mounts
	 * @param non-empty-list<MountProviderArgs> $mountProviderArgs The data for the mount which should be provided.
	 *                                                             Contains the mount information and root-cache-entry
	 *                                                             for each mount the system knows about
	 *                                                             in the scope of the setup request.
	 * @param IStorageFactory $loader
	 * @return array<string, IMountPoint> IMountPoint instances, indexed by mount-point
	 */
	public function getMountsForPath(
		string $setupPathHint,
		bool $forChildren,
		array $mountProviderArgs,
		IStorageFactory $loader,
	): array;
}
