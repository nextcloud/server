<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage;

use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 */
interface Storage extends IStorage, ILockingStorage {
	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage|null (optional) the storage to pass to the cache
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '', $storage = null);

	/**
	 * get a scanner instance for the storage
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the scanner
	 * @return \OC\Files\Cache\Scanner
	 */
	public function getScanner($path = '', $storage = null);

	/**
	 * get a watcher instance for the cache
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the watcher
	 * @return \OC\Files\Cache\Watcher
	 */
	public function getWatcher($path = '', $storage = null);

	/**
	 * get a propagator instance for the cache
	 *
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the watcher
	 * @return \OC\Files\Cache\Propagator
	 */
	public function getPropagator($storage = null);

	/**
	 * get a updater instance for the cache
	 *
	 * @param \OC\Files\Storage\Storage (optional) the storage to pass to the watcher
	 * @return \OC\Files\Cache\Updater
	 */
	public function getUpdater($storage = null);

	/**
	 * @return \OC\Files\Cache\Storage
	 */
	public function getStorageCache();

	/**
	 * @param string $path
	 * @return array|null
	 */
	public function getMetaData($path);

	/**
	 * Get the contents of a directory with metadata
	 *
	 * @param string $directory
	 * @return \Traversable an iterator, containing file metadata
	 *
	 * The metadata array will contain the following fields
	 *
	 * - name
	 * - mimetype
	 * - mtime
	 * - size
	 * - etag
	 * - storage_mtime
	 * - permissions
	 */
	public function getDirectoryContent($directory): \Traversable;
}
