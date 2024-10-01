<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Cache;

use OCP\ICache;

/**
 * In-memory cache with a capacity limit to keep memory usage in check
 *
 * Uses a simple FIFO expiry mechanism
 * @template T
 * @deprecated 25.0.0 use OCP\Cache\CappedMemoryCache instead
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
