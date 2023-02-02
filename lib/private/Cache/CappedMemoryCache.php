<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OC\Cache;

use OCP\ICache;

/**
 * In-memory cache with a capacity limit to keep memory usage in check
 *
 * Uses a simple FIFO expiry mechanism
 * @template T
 * @deprecated use OCP\Cache\CappedMemoryCache instead
 */
class CappedMemoryCache implements ICache, \ArrayAccess {
	private $capacity;
	/** @var T[] */
	private $cache = [];

	public function __construct($capacity = 512) {
		$this->capacity = $capacity;
	}

	public function hasKey($key): bool {
		return isset($this->cache[$key]);
	}

	/**
	 * @return ?T
	 */
	public function get($key) {
		return $this->cache[$key] ?? null;
	}

	/**
	 * @param string $key
	 * @param T $value
	 * @param int $ttl
	 * @return bool
	 */
	public function set($key, $value, $ttl = 0): bool {
		if (is_null($key)) {
			$this->cache[] = $value;
		} else {
			$this->cache[$key] = $value;
		}
		$this->garbageCollect();
		return true;
	}

	public function remove($key) {
		unset($this->cache[$key]);
		return true;
	}

	public function clear($prefix = '') {
		$this->cache = [];
		return true;
	}

	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	/**
	 * @return T
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet($offset) {
		return $this->cache[$offset];
	}

	/**
	 * @param string $offset
	 * @param T $value
	 * @return void
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	/**
	 * @return T[]
	 */
	public function getData() {
		return $this->cache;
	}


	private function garbageCollect() {
		while (count($this->cache) > $this->capacity) {
			reset($this->cache);
			$key = key($this->cache);
			$this->remove($key);
		}
	}

	public static function isAvailable(): bool {
		return true;
	}
}
