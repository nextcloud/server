<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
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
	 * @return IMemcache|null
	 * @since 13.0.0
	 * @since 28.0.0 return type is nullable but the method will continue to return an object for backwards compatibility. Future versions will only return an object if distributed cache is available.
	 */
	public function createLocking(string $prefix = ''): ?IMemcache;

	/**
	 * create a distributed cache instance
	 *
	 * @param string $prefix
	 * @return ICache|null a cache implementation
	 * @since 13.0.0
	 * @since 28.0.0 return type is nullable but the method will continue to return an object for backwards compatibility. Future versions will only return an object if distributed cache is available.
	 */
	public function createDistributed(string $prefix = ''): ?ICache;

	/**
	 * Create a local cache instance
	 *
	 * @param string $prefix
	 *
	 * @return ICache|null a cache implementation
	 * @since 13.0.0
	 * @since 28.0.0 return type is nullable but the method will continue to return an object for backwards compatibility. Future versions will only return an object if local cache is available.
	 */
	public function createLocal(string $prefix = ''): ?ICache;

	/**
	 * Create an in-memory cache instance
	 *
	 * Useful for remembering values inside one process. Cache memory is cleared
	 * when the object is garbage-collected. Implementation may also expire keys
	 * earlier when the TTL is reached or too much memory is consumed.
	 *
	 * @param int $capacity
	 * @return ICache
	 * @since 28.0.0
	 */
	public function createInMemory(int $capacity = 512): ICache;
}
