<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Config;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\NotFoundException;
use OCP\IUser;

/**
 * Cache mounts points per user in the cache so we can easily look them up
 *
 * @since 9.0.0
 */
#[Consumable(since: '9.0.0.')]
interface IUserMountCache {
	/**
	 * Register mounts for a user to the cache
	 *
	 * @param IUser $user
	 * @param IMountPoint[] $mounts
	 * @param array|null $mountProviderClasses
	 * @since 9.0.0
	 */
	public function registerMounts(IUser $user, array $mounts, ?array $mountProviderClasses = null): void;

	/**
	 * Get all cached mounts for a user
	 *
	 * @param IUser $user
	 * @return ICachedMountInfo[]
	 * @since 9.0.0
	 */
	public function getMountsForUser(IUser $user): array;

	/**
	 * Get all cached mounts by storage
	 *
	 * @param int $numericStorageId
	 * @param string|null $user limit the results to a single user @since 12.0.0
	 * @return ICachedMountInfo[]
	 * @since 9.0.0
	 */
	public function getMountsForStorageId(int $numericStorageId, ?string $user = null): array;

	/**
	 * Get all cached mounts by root
	 *
	 * @param int $rootFileId
	 * @return ICachedMountInfo[]
	 * @since 9.0.0
	 */
	public function getMountsForRootId(int $rootFileId): array;

	/**
	 * Get all cached mounts that contain a file.
	 *
	 * @param int $fileId
	 * @param string|null $user optionally restrict the results to a single user @since 12.0.0
	 * @return ICachedMountFileInfo[]
	 * @since 9.0.0
	 */
	public function getMountsForFileId(int $fileId, ?string $user = null): array;

	/**
	 * Remove all cached mounts for a user.
	 *
	 * @since 9.0.0
	 */
	public function removeUserMounts(IUser $user): void;

	/**
	 * Remove all mounts for a user and storage.
	 * @since 9.0.0
	 */
	public function removeUserStorageMount(int $storageId, string $userId): void;

	/**
	 * Remove all cached mounts for a storage.
	 *
	 * @since 9.0.0
	 */
	public function remoteStorageMounts(int $storageId): void;

	/**
	 * Get the used space for users.
	 *
	 * Note that this only includes the space in their home directory,
	 * not any incoming shares or external storage.
	 *
	 * @param IUser[] $users
	 * @return array<string, int> Mapping from userId to their used space.
	 * @since 13.0.0
	 */
	public function getUsedSpaceForUsers(array $users): array;

	/**
	 * Clear all entries from the in-memory cache
	 *
	 * @since 20.0.0
	 */
	public function clear(): void;

	/**
	 * Get all cached mounts for a user
	 *
	 * @param IUser $user
	 * @param string $path
	 * @return ICachedMountInfo
	 * @throws NotFoundException
	 * @since 24.0.0
	 */
	public function getMountForPath(IUser $user, string $path): ICachedMountInfo;

	/**
	 * Get all cached mounts for a user inside a path
	 *
	 * @param IUser $user
	 * @param string $path
	 * @return ICachedMountInfo[]
	 * @throws NotFoundException
	 * @since 24.0.0
	 */
	public function getMountsInPath(IUser $user, string $path): array;
}
