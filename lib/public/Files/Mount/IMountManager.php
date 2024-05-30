<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Mount;

use OCP\Files\Config\ICachedMountInfo;

/**
 * Interface IMountManager
 *
 * Manages all mounted storages in the system
 * @since 8.2.0
 */
interface IMountManager {
	/**
	 * Add a new mount
	 *
	 * @param IMountPoint $mount
	 * @since 8.2.0
	 */
	public function addMount(IMountPoint $mount);

	/**
	 * Remove a mount
	 *
	 * @param string $mountPoint
	 * @since 8.2.0
	 */
	public function removeMount(string $mountPoint);

	/**
	 * Change the location of a mount
	 *
	 * @param string $mountPoint
	 * @param string $target
	 * @since 8.2.0
	 */
	public function moveMount(string $mountPoint, string $target);

	/**
	 * Find the mount for $path
	 *
	 * @param string $path
	 * @return IMountPoint
	 * @since 8.2.0
	 */
	public function find(string $path): ?IMountPoint;

	/**
	 * Find all mounts in $path
	 *
	 * @param string $path
	 * @return IMountPoint[]
	 * @since 8.2.0
	 */
	public function findIn(string $path): array;

	/**
	 * Remove all registered mounts
	 *
	 * @since 8.2.0
	 */
	public function clear();

	/**
	 * Find mounts by storage id
	 *
	 * @param string $id
	 * @return IMountPoint[]
	 * @since 8.2.0
	 */
	public function findByStorageId(string $id): array;

	/**
	 * @return IMountPoint[]
	 * @since 8.2.0
	 */
	public function getAll(): array;

	/**
	 * Find mounts by numeric storage id
	 *
	 * @param int $id
	 * @return IMountPoint[]
	 * @since 8.2.0
	 */
	public function findByNumericId(int $id): array;

	/**
	 * Return the mount matching a cached mount info (or mount file info)
	 *
	 * @param ICachedMountInfo $info
	 *
	 * @return IMountPoint|null
	 * @since 28.0.0
	 */
	public function getMountFromMountInfo(ICachedMountInfo $info): ?IMountPoint;
}
