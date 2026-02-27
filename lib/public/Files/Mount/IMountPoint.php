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
	 * Get complete path to the mount point.
	 *
	 * @return string
	 * @since 8.0.0
	 */
	public function getMountPoint();

	/**
	 * Set the complete path the mountpoint.
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
	/**
	 * Returns the storage backend mounted at this point.
	 *
	 * Result may be memoized for subsequent calls.
	 *
	 * @return \OCP\Files\Storage\IStorage|null The mounted storage backend, or null if initialization failed.
	 * @since 8.0.0
	 */
	public function getStorage();

	/**
	 * Get the storage's string identifier from the storage backend.
	 *
	 * If the identifier exceeds 64 characters, it will be MD5 hashed.
	 *
	 * @return string|null Storage id, or null if the storage cannot be initialized.
	 * @since 8.0.0
	 */
	public function getStorageId();

	/**
	 * Get the storage's numeric identifier from the cache.
	 *
	 * This integer ID is more efficient for database operations and lookups compared
	 * to the string-based storage ID.
	 *
	 * @return int Numeric storage identifier, or -1 if storage cannot be initialized
	 * @since 9.1.0
	 */
	public function getNumericStorageId();

	/**
	 * Returns the path relative to the mount point for a given absolute path.
	 *
	 * Converts an absolute path within the Nextcloud filesystem to its internal representation,
	 * i.e., the path inside the mounted storage. If the path corresponds to the root of the
	 * mount point, an empty string is returned.
	 *
	 * @param string $path Absolute path to a file or folder
	 * @return string Path relative to this mount point ("" for root)
	 * @since 8.0.0
	 */
	public function getInternalPath($path);

	/**
	 * Applies a callable wrapper to the underlying storage.
	 *
	 * The wrapper is typically used to modify or enhance the storage behavior (e.g., for encryption or logging).
	 * The callable receives the mount point, the original storage, and the mount point instance as arguments.
	 * If the storage cannot be initialized, this method has no effect.
	 *
	 * @param callable $wrapper Callable of the form function(string $mountPoint, \OCP\Files\Storage\IStorage $storage, IMountPoint $mount): \OCP\Files\Storage\IStorage
	 * @return void
	 * @since 8.0.0
	 */
	public function wrapStorage($wrapper);

	/**
	 * Returns the value of a mount option by name from the mount configuration.
	 *
	 * If the option is not present, returns the provided default value.
	 *
	 * @param string $name The name of the mount option to retrieve
	 * @param mixed $default The value to return if the option is not set
	 * @return mixed Option value if set, otherwise $default
	 * @since 8.0.0
	 */
	public function getOption($name, $default);

	/**
	 * Returns all mount options configured for this mount point.
	 *
	 * Provides an associative array of all options specific to this mount,
	 * with string keys and mixed values.
	 *
	 * @return array Associative array of mount options (may be empty)
	 * @since 8.1.0
	 */
	public function getOptions();

	/**
	 * Returns the file id of the root folder for this storage.
	 *
	 * This is the unique id (from the file cache) of the root entry for the storage mounted at this point.
	 * Returns -1 if the storage is not available or has not been scanned yet.
	 *
	 * @return int File id of the root folder, or -1 if unavailable.
	 * @since 9.1.0
	 */
	public function getStorageRootId();

	/**
	 * Returns the unique identifier for this mount, if configured.
	 *
	 * This id is typically assigned by the system or storage backend when a mount
	 * point is created and persisted. Returns null if the mount does not have an id,
	 * such as in the case of temporary or system mounts.
	 *
	 * @return int|null Mount point id, or null if not applicable.
	 * @since 9.1.0
	 */
	public function getMountId();

	/**
	 * Returns the type of this mount point as a string.
	 *
	 * The mount type is used to distinguish between different sources or kinds
	 * of mounts, such as 'home', 'shared', 'external', etc. Defaults to an
	 * empty string if not set.
	 *
	 * @return string The mount point type identifier (e.g., 'home', 'shared', 'external').
	 * @since 12.0.0
	 */
	public function getMountType();

	/**
	 * Returns the fully-qualified class name of the mount provider for this mount.
	 *
	 * The mount provider is the service or class that created and manages this mount
	 * (for example, a class handling user homes, external storage, or shared mounts).
	 *
	 * @return string Fully-qualified class name of the provider, or empty string if the provider is not set.
	 * @since 24.0.0
	 */
	public function getMountProvider(): string;
}
