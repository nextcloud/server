<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Interface for memcache backends that support setting ttl after the value is set
 *
 * @since 8.2.2
 */
interface IMemcacheTTL extends IMemcache {
	/**
	 * Set the ttl for an existing value
	 *
	 * @param string $key
	 * @param int $ttl time to live in seconds
	 * @since 8.2.2
	 */
	public function setTTL(string $key, int $ttl);

	/**
	 * Get the ttl for an existing value, in seconds till expiry
	 *
	 * @return int|false
	 * @since 27
	 */
	public function getTTL(string $key): int|false;
	/**
	 * Set the ttl for an existing value if the value matches
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl time to live in seconds
	 * @since 27
	 */
	public function compareSetTTL(string $key, $value, int $ttl): bool;
}
