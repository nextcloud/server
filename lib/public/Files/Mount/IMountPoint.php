<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Mount;

/**
 * A storage mounted to folder on the filesystem
 * @since 8.0.0
 */
interface IMountPoint {
	/**
	 * get complete path to the mount point
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getMountPoint();

	/**
	 * Set the mountpoint
	 *
	 * @param string $mountPoint new mount point
	 * @since 8.0.0
	 */
	public function setMountPoint($mountPoint);

	/**
	 * Get the storage that is mounted
	 *
	 * @return \OCP\Files\Storage\IStorage|null
	 * @since 8.0.0
	 */
	public function getStorage();

	/**
	 * Get the id of the storages
	 *
	 * @return string|null
	 * @since 8.0.0
	 */
	public function getStorageId();

	/**
	 * Get the id of the storages
	 *
	 * @return int|null
	 * @since 9.1.0
	 */
	public function getNumericStorageId();

	/**
	 * Get the path relative to the mountpoint
	 *
	 * @param string $path absolute path to a file or folder
	 * @return string
	 * @since 8.0.0
	 */
	public function getInternalPath($path);

	/**
	 * Apply a storage wrapper to the mounted storage
	 *
	 * @param callable $wrapper
	 * @since 8.0.0
	 */
	public function wrapStorage($wrapper);

	/**
	 * Get a mount option
	 *
	 * @param string $name Name of the mount option to get
	 * @param mixed $default Default value for the mount option
	 * @return mixed
	 * @since 8.0.0
	 */
	public function getOption($name, $default);

	/**
	 * Get all options for the mount
	 *
	 * @return array
	 * @since 8.1.0
	 */
	public function getOptions();

	/**
	 * Get the file id of the root of the storage
	 *
	 * @return int
	 * @since 9.1.0
	 */
	public function getStorageRootId();

	/**
	 * Get the id of the configured mount
	 *
	 * @return int|null mount id or null if not applicable
	 * @since 9.1.0
	 */
	public function getMountId();

	/**
	 * Get the type of mount point, used to distinguish things like shares and external storage
	 * in the web interface
	 *
	 * @return string
	 * @since 12.0.0
	 */
	public function getMountType();

	/**
	 * Get the class of the mount provider that this mount originates from
	 *
	 * @return string
	 * @since 24.0.0
	 */
	public function getMountProvider(): string;
}
