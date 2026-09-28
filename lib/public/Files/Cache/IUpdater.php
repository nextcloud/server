<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Cache;

use OCP\Files\Storage\IStorage;

/**
 * Update the cache and propagate changes
 *
 * @since 9.0.0
 */
interface IUpdater {
	/**
	 * Get the propagator for etags and mtime for the view the updater works on
	 *
	 * @return IPropagator
	 * @since 9.0.0
	 */
	public function getPropagator();

	/**
	 * Propagate etag and mtime changes for the parent folders of $path up to the root of the filesystem
	 *
	 * @param string $path the path of the file to propagate the changes for
	 * @param int|null $time the timestamp to set as mtime for the parent folders, if left out the current time is used
	 * @since 9.0.0
	 */
	public function propagate($path, $time = null);

	/**
	 * Update the cache for $path and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 * @param int $time
	 * @since 9.0.0
	 */
	public function update($path, $time = null, ?int $sizeDifference = null);

	/**
	 * Remove $path from the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param string $path
	 * @since 9.0.0
	 */
	public function remove($path);

	/**
	 * Rename a file or folder in the cache and update the size, etag and mtime of the parent folders
	 *
	 * @param IStorage $sourceStorage
	 * @param string $source
	 * @param string $target
	 * @since 9.0.0
	 */
	public function renameFromStorage(IStorage $sourceStorage, $source, $target);

	/**
	 * Copy a file or folder in the cache and update the size, etag and mtime of the parent folders
	 *
	 * @since 31.0.0
	 */
	public function copyFromStorage(IStorage $sourceStorage, string $source, string $target): void;
}
