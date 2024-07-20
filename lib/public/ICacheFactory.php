<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP;

/**
 * Interface ICacheFactory
 *
 * @since 7.0.0
 */
interface ICacheFactory {
	/**
	 * Check if any memory cache backend is available
	 *
	 * @return bool
	 * @since 7.0.0
	 */
	public function isAvailable(): bool;

	/**
	 * Check if a local memory cache backend is available
	 *
	 * @return bool
	 * @since 14.0.0
	 */
	public function isLocalCacheAvailable(): bool;

	/**
	 * create a cache instance for storing locks
	 *
	 * @param string $prefix
	 * @return IMemcache
	 * @since 13.0.0
	 */
	public function createLocking(string $prefix = ''): IMemcache;

	/**
	 * create a distributed cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 * @since 13.0.0
	 */
	public function createDistributed(string $prefix = ''): ICache;

	/**
	 * create a local cache instance
	 *
	 * @param string $prefix
	 * @return ICache
	 * @since 13.0.0
	 */
	public function createLocal(string $prefix = ''): ICache;

	/**
	 * Create an in-memory cache instance
	 *
	 * Useful for remembering values inside one process. Cache memory is cleared
	 * when the object is garbage-collected. Implementation may also expire keys
	 * earlier when the TTL is reached or too much memory is consumed.
	 *
	 * Cache keys are local to the cache object. When building two in-memory
	 * caches, there is no data exchange between the instances.
	 *
	 * @param int $capacity maximum number of cache keys
	 * @return ICache
	 * @since 28.0.0
	 */
	public function createInMemory(int $capacity = 512): ICache;
}
