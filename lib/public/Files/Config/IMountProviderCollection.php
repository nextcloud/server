<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Config;

use OCP\IUser;

/**
 * Manages the different mount providers
 * @since 8.0.0
 */
interface IMountProviderCollection {
	/**
	 * Get all configured mount points for the user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 8.0.0
	 */
	public function getMountsForUser(IUser $user);

	/**
	 * Get the configured mount points for the user from a specific mount provider
	 *
	 * @param \OCP\IUser $user
	 * @param class-string<IMountProvider>[] $mountProviderClasses
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 24.0.0
	 */
	public function getUserMountsForProviderClasses(IUser $user, array $mountProviderClasses): array;

	/**
	 * Get the configured home mount for this user
	 *
	 * @param \OCP\IUser $user
	 * @return \OCP\Files\Mount\IMountPoint
	 * @since 9.1.0
	 */
	public function getHomeMountForUser(IUser $user);

	/**
	 * Add a provider for mount points
	 *
	 * @param \OCP\Files\Config\IMountProvider $provider
	 * @since 8.0.0
	 */
	public function registerProvider(IMountProvider $provider);

	/**
	 * Add a filter for mounts
	 *
	 * @param callable $filter (IMountPoint $mountPoint, IUser $user) => boolean
	 * @since 14.0.0
	 */
	public function registerMountFilter(callable $filter);

	/**
	 * Add a provider for home mount points
	 *
	 * @param \OCP\Files\Config\IHomeMountProvider $provider
	 * @since 9.1.0
	 */
	public function registerHomeProvider(IHomeMountProvider $provider);

	/**
	 * Get the mount cache which can be used to search for mounts without setting up the filesystem
	 *
	 * @return IUserMountCache
	 * @since 9.0.0
	 */
	public function getMountCache();

	/**
	 * Get all root mountpoints
	 *
	 * @return \OCP\Files\Mount\IMountPoint[]
	 * @since 20.0.0
	 */
	public function getRootMounts(): array;
}
