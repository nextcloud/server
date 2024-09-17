<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage;

use OC\Files\Cache\Cache;
use OC\Files\Cache\Propagator;
use OC\Files\Cache\Scanner;
use OC\Files\Cache\Updater;
use OC\Files\Cache\Watcher;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 */
interface Storage extends IStorage, ILockingStorage {
	/**
	 * @inheritDoc
	 * @return Cache
	 */
	public function getCache($path = '', $storage = null);

	/**
	 * @inheritDoc
	 * @return Scanner
	 */
	public function getScanner($path = '', $storage = null);

	/**
	 * @inheritDoc
	 * @return Watcher
	 */
	public function getWatcher($path = '', $storage = null);

	/**
	 * @inheritDoc
	 * @return Propagator
	 */
	public function getPropagator($storage = null);

	/**
	 * @inheritDoc
	 * @return Updater
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

	/**
	 * Check if a filepath is available/unused
	 *
	 * This is usually the inverse of `file_exists` but some filesystems might have additional restrictions for
	 * which file names are available.
	 * For example with case-insensitive filesystems where names that only differ by case would conflict.
	 *
	 * @param string $path
	 * @return bool
	 */
	public function pathAvailable(string $path): bool;
}
