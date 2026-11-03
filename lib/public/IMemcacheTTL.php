<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP;

/**
 * Interface for memcache backends that can update and retrieve the TTL of an
 * existing cache entry.
 *
 * @since 8.2.2
 */
interface IMemcacheTTL extends IMemcache {
	/**
	 * Update the TTL of an existing cache entry.
	 *
	 * Implementations may treat a non-positive TTL as immediate expiry.
	 *
	 * @param string $key Cache key
	 * @param int $ttl TTL in seconds. 0 uses the backend default TTL, negative
	 *     values expire the entry immediately.
	 * @since 8.2.2
	 */
	public function setTTL(string $key, int $ttl);

	/**
	 * Get the remaining TTL of an existing cache entry.
	 *
	 * @param string $key Cache key
	 * @return int Remaining TTL in whole seconds, or false if the key has no
	 *     positive remaining TTL or does not exist
	 * @since 27.0.0
	 */
	public function getTTL(string $key): int|false;

	/**
	 * Atomically update the TTL if the existing value matches.
	 *
	 * Implementations may treat a non-positive TTL as immediate expiry.
	 *
	 * @param string $key Cache key
	 * @param mixed $value Expected current value
	 * @param int $ttl TTL in seconds.  uses the backend default TTL, negative
	 *     values expire the entry immediately.
	 * @return bool True if the value matched and the TTL was updated, false otherwise
	 * @since 27.0.0
	 */
	public function compareSetTTL(string $key, mixed $value, int $ttl): bool;
}
