<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\Files\Mount;

/**
 * A storage mounted to folder on the filesystem
 */
interface IMountPoint {

	/**
	 * get complete path to the mount point
	 *
	 * @return string
	 */
	public function getMountPoint();

	/**
	 * Set the mountpoint
	 *
	 * @param string $mountPoint new mount point
	 */
	public function setMountPoint($mountPoint);

	/**
	 * Get the storage that is mounted
	 *
	 * @return \OC\Files\Storage\Storage
	 */
	public function getStorage();

	/**
	 * Get the id of the storages
	 *
	 * @return string
	 */
	public function getStorageId();

	/**
	 * Get the path relative to the mountpoint
	 *
	 * @param string $path absolute path to a file or folder
	 * @return string
	 */
	public function getInternalPath($path);

	/**
	 * Apply a storage wrapper to the mounted storage
	 *
	 * @param callable $wrapper
	 */
	public function wrapStorage($wrapper);

	/**
	 * Get a mount option
	 *
	 * @param string $name Name of the mount option to get
	 * @param mixed $default Default value for the mount option
	 * @return mixed
	 */
	public function getOption($name, $default);
}
