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
 *
 * @since 8.1.0
 */
interface IMemcache extends ICache {
	/**
	 * Set a value in the cache if it's not already stored
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 * @since 8.1.0
	 */
	public function add($key, $value, $ttl = 0);

	/**
	 * Increase a stored number
	 *
	 * If no value is stored with the key, it will behave as if a 0 was stored.
	 * If a non-numeric value is stored, the operation will fail and `false` is returned.
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 * @since 8.1.0
	 */
	public function inc($key, $step = 1);

	/**
	 * Decrease a stored number
	 *
	 *  If no value is stored with the key, the operation will fail and `false` is returned.
	 *  If a non-numeric value is stored, the operation will fail and `false` is returned.
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 * @since 8.1.0
	 */
	public function dec($key, $step = 1);

	/**
	 * Compare and set
	 *
	 *  Set $key to $new only if it's current value is $new
	 *
	 * @param string $key
	 * @param mixed $old
	 * @param mixed $new
	 * @return bool true if the value was successfully set or false if $key wasn't set to $old
	 * @since 8.1.0
	 */
	public function cas($key, $old, $new);

	/**
	 * Compare and delete
	 *
	 *  Delete $key if the stored value is equal to $old
	 *
	 * @param string $key
	 * @param mixed $old
	 * @return bool true if the value was successfully deleted or false if $key wasn't set to $old
	 * @since 8.1.0
	 */
	public function cad($key, $old);

	/**
	 * Negative compare and delete
	 *
	 * Delete $key if the stored value is not equal to $old
	 *
	 * @param string $key
	 * @param mixed $old
	 * @return bool true if the value was successfully deleted or false if $key was set to $old or is not set
	 * @since 30.0.0
	 */
	public function ncad(string $key, mixed $old): bool;
}
