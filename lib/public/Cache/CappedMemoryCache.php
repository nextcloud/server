<?php
/**
 * Part of Nextcloud's public caching API: CappedMemoryCache class 
 */

/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Cache;

use OCP\ICache;

/**
 * A simple, fast, memory-only FIFO cache with a configurable size limit.
 *
 * Features:
 * - Stores key-value pairs in memory, but only up to a specified capacity.
 * - Implements standard Nextcloud cache (OCP\ICache) and PHP ArrayAccess interfaces.
 * - Designed for temporary, in-memory caching in Nextcloud apps or core.
 * - Uses a simple FIFO expiry mechanism.
 *
 * Benefits:
 * - No administrative configuration/dependencies (always available).
 * - Can be used via normal method calls (OCP\ICache) or PHP array syntax.
 * - Automatically removes oldest entries when limit is reached.
 * - Can be used like an array or via standard Nextcloud cache operations.
 * - Lowest latency possible.
 * - Reduces load on distributed cache.
 * - Offers consumers flexibility to choose best cache combo for a use case
 *
 * Caveats:
 * - Not at all shared (even among processes/transactions/requests on the same host).
 * - Highly transient (end of process/transaction/request).
 * - Consumes RAM on the local host.
 *
 * Usage examples:
 *
 * ```php
 * [...]
 * use OCP\Cache\CappedMemoryCache;
 * [...]
 * $cache = new CappedMemoryCache(64); // capacity of 64 items
 * $cache->set('foo', 'bar'); // give key 'foo' value 'bar' 
 * if ($cache->hasKey('foo')) {
 *     echo $cache->get('foo'); // outputs 'bar'
 * }
 * ```
 * Or using array syntax:
 * ```php
 * [...]
 * $cache['baz'] = 'qux';
 * if (isset($cache['baz']) {
 *     echo $cache['baz']; // outputs 'qux'
 * }
 * ```
 *
 * @link https://docs.nextcloud.com/server/latest/developer_manual/basics/caching.html
 * @link https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/caching_configuration.html
 *
 * @template T
 * @template-implements \ArrayAccess<string, T>
 * @since 25.0.0
 */
class CappedMemoryCache implements ICache, \ArrayAccess {
	
	/**
	 * Maximum number of entries allowed in the cache.
	 *
	 * @var int
	 */
	private int $capacity;
	
	/**
	 * Internal cache data storage.
	 *
	 * @var array<string, T>
	 */	
	private array $cache = [];

	/**
 	 * Current count of items stored in cache.
	 *
	 * @var int
	 */
	private int $itemCount = 0;

	/**
	 * Constructor.
	 *
	 * @param int $capacity Maximum number of items in the cache. Defaults to 512.
	 * @since 25.0.0
	 */
	public function __construct(int $capacity = 512) {
		$this->capacity = $capacity;
	}

	//
	// Cache Operations
	//
	
	/**
	 * Checks if the cache contains the specified key.
	 *
	 * @param string $key
	 * @return bool True if the key exists, false otherwise.
	 * @since 25.0.0
	 */
	public function hasKey($key): bool {
		return isset($this->cache[$key]);
	}

	/**
	 * Retrieves the value for the specified key from the cache.
	 *
	 * @param string $key
	 * @return T|null The value, or null if the key does not exist.
	 * @since 25.0.0
	 */
	public function get($key) {
		return $this->cache[$key] ?? null;
	}

	/**
	 * Adds or updates a value in the cache.
	 * If capacity is exceeded, evicts the oldest entries.
	 *
	 * @param string $key
	 * @param T $value
	 * @param int $ttl Unused. Included for interface compatibility.
	 * @return bool True on success.
	 * @since 25.0.0
	 */
	public function set($key, $value, $ttl = 0): bool {
		$isNewKey = !isset($this->cache[$key]) || $key === null;

		if ($key !== null) {
			$this->cache[$key] = $value;
		} else { // for offsetSet() when $key is null
			$this->cache[] = $value;
		}

		if ($isNewKey) {
			$this->itemCount++;
		}
		$this->garbageCollect();
		return true;
	}

	/**
	 * Removes the specified key from the cache.
	 *
	 * @param string $key
	 * @return bool True on success.
	 * @since 25.0.0
	 */
	public function remove($key): bool {
		if (isset($this->cache[$key])) {
			unset($this->cache[$key]);
			$this->itemCount--;
		}
		return true;
	}

	/**
	 * Clears all cache entries.
	 *
	 * @param string $prefix Unused. Included for interface compatibility.
	 * @return bool True on success.
	 * @since 25.0.0
	 */
	public function clear($prefix = ''): bool {
		$this->cache = [];
		$this->itemCount = 0;
		return true;
	}

	//
	// ArrayAccess Support
	//

	/**
	 * Determines if an offset exists in the cache.
	 *
	 * @param string $offset
	 * @return bool
	 * @since 25.0.0
	 */
	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	/**
	 * Retrieves the value at the specified offset.
	 *
	 * @param string $offset
	 * @return T|null
	 * @since 25.0.0
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet($offset) {
		// TODO: Any side effects with the new GC tracking?
		return $this->cache[$offset];
	}

	/**
	 * Sets the value at the specified offset.
	 *
	 * @param string $offset
	 * @param T $value
	 * @since 25.0.0
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * Unsets the value at the specified offset.
	 *
	 * @param string $offset
	 * @since 25.0.0
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	//
	// Utilities
	//
	
	/**
	 * Returns all cache data as an associative array.
	 *
	 * @return array<string, T>
	 * @since 25.0.0
	 */
	public function getData(): array {
		return $this->cache;
	}

	/**
	 * Removes oldest entries if cache exceeds its capacity.
	 *
	 * @return void
	 * @since 25.0.0
	 */
	private function garbageCollect(): void {
		while ($this->itemCount > $this->capacity) {
			reset($this->cache);
			$key = key($this->cache);
			$this->remove($key);
		}
	}

	//
	// Static Methods
	//

	/**
	 * Indicates if this cache implementation is available.
	 *
	 * @return bool Always returns true.
	 * @since 25.0.0
	 */
	public static function isAvailable(): bool {
		return true;
	}
}
