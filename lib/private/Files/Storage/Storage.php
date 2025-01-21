<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Files\Storage;

use OCP\Files\Cache\ICache;
use OCP\Files\Cache\IPropagator;
use OCP\Files\Cache\IScanner;
use OCP\Files\Cache\IUpdater;
use OCP\Files\Cache\IWatcher;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;

/**
 * Provide a common interface to all different storage options
 *
 * All paths passed to the storage are relative to the storage and should NOT have a leading slash.
 */
interface Storage extends IStorage, ILockingStorage {
	public function getCache(string $path = '', ?IStorage $storage = null): ICache;

	public function getScanner(string $path = '', ?IStorage $storage = null): IScanner;

	public function getWatcher(string $path = '', ?IStorage $storage = null): IWatcher;

	public function getPropagator(?IStorage $storage = null): IPropagator;

	public function getUpdater(?IStorage $storage = null): IUpdater;

	public function getStorageCache(): \OC\Files\Cache\Storage;

	public function getMetaData(string $path): ?array;

	/**
	 * Get the contents of a directory with metadata
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
	public function getDirectoryContent(string $directory): \Traversable;
}
