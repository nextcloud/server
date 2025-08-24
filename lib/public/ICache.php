<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP;

/**
 * This interface defines method for accessing the file based user cache.
 * @since 6.0.0
 */
interface ICache {
	/**
	 * @since 30.0.0
	 */
	public const DEFAULT_TTL = 24 * 60 * 60;

	/**
	 * Get a value from the user cache
	 * @param string $key
	 * @return mixed
	 * @since 6.0.0
	 */
	public function get($key);

	/**
	 * Set a value in the user cache
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 * @since 6.0.0
	 */
	public function set($key, $value, $ttl = 0);

	/**
	 * Check if a value is set in the user cache
	 * @param string $key
	 * @return bool
	 * @since 6.0.0
	 * @deprecated 9.1.0 Directly read from GET to prevent race conditions
	 */
	public function hasKey($key);

	/**
	 * Remove an item from the user cache
	 * @param string $key
	 * @return bool
	 * @since 6.0.0
	 */
	public function remove($key);

	/**
	 * Clear the user cache of all entries starting with a prefix
	 * @param string $prefix (optional)
	 * @return bool
	 * @since 6.0.0
	 */
	public function clear($prefix = '');

	/**
	 * Check if the cache implementation is available
	 * @since 24.0.0
	 */
	public static function isAvailable(): bool;
}
