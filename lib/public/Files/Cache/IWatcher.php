<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Files\Cache;

/**
 * check the storage backends for updates and change the cache accordingly
 *
 * @since 9.0.0
 */
interface IWatcher {
	/**
	 * @since 9.0.0
	 */
	public const CHECK_NEVER = 0; // never check the underlying filesystem for updates

	/**
	 * @since 9.0.0
	 */
	public const CHECK_ONCE = 1; // check the underlying filesystem for updates once every request for each file

	/**
	 * @since 9.0.0
	 */
	public const CHECK_ALWAYS = 2; // always check the underlying filesystem for updates

	/**
	 * @param int $policy either IWatcher::CHECK_NEVER, IWatcher::CHECK_ONCE, IWatcher::CHECK_ALWAYS
	 * @since 9.0.0
	 */
	public function setPolicy($policy);

	/**
	 * @return int either IWatcher::CHECK_NEVER, IWatcher::CHECK_ONCE, IWatcher::CHECK_ALWAYS
	 * @since 9.0.0
	 */
	public function getPolicy();

	/**
	 * check $path for updates and update if needed
	 *
	 * @param string $path
	 * @param ICacheEntry|null $cachedEntry
	 * @return boolean true if path was updated
	 * @since 9.0.0
	 */
	public function checkUpdate($path, $cachedEntry = null);

	/**
	 * Update the cache for changes to $path
	 *
	 * @param string $path
	 * @param ICacheEntry $cachedData
	 * @since 9.0.0
	 */
	public function update($path, $cachedData);

	/**
	 * Check if the cache for $path needs to be updated
	 *
	 * @param string $path
	 * @param ICacheEntry $cachedData
	 * @return bool
	 * @since 9.0.0
	 */
	public function needsUpdate($path, $cachedData);

	/**
	 * remove deleted files in $path from the cache
	 *
	 * @param string $path
	 * @since 9.0.0
	 */
	public function cleanFolder($path);

	/**
	 * register a callback to be called whenever the watcher triggers and update
	 * @since 31.0.0
	 */
	public function onUpdate(callable $callback): void;
}
