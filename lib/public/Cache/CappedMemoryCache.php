<?php

/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Cache;

use OCP\ICache;

/**
 * In-memory cache with a capacity limit to keep memory usage in check
 *
 * Uses a simple FIFO expiry mechanism
 *
 * @since 25.0.0
 * @template T
 * @template-implements \ArrayAccess<string,T>
 */
class CappedMemoryCache implements ICache, \ArrayAccess {
	private int $capacity;
	/** @var T[] */
	private array $cache = [];

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function __construct(int $capacity = 512) {
		$this->capacity = $capacity;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function hasKey($key): bool {
		return isset($this->cache[$key]);
	}

	/**
	 * @return ?T
	 * @since 25.0.0
	 */
	public function get($key) {
		return $this->cache[$key] ?? null;
	}

	/**
	 * @inheritdoc
	 * @param string $key
	 * @param T $value
	 * @param int $ttl
	 * @since 25.0.0
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

	/**
	 * @since 25.0.0
	 */
	public function remove($key): bool {
		unset($this->cache[$key]);
		return true;
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function clear($prefix = ''): bool {
		$this->cache = [];
		return true;
	}

	/**
	 * @since 25.0.0
	 */
	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	/**
	 * @inheritdoc
	 * @return T
	 * @since 25.0.0
	 */
	#[\ReturnTypeWillChange]
	public function &offsetGet($offset) {
		return $this->cache[$offset];
	}

	/**
	 * @inheritdoc
	 * @param string $offset
	 * @param T $value
	 * @since 25.0.0
	 */
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}

	/**
	 * @return T[]
	 * @since 25.0.0
	 */
	public function getData(): array {
		return $this->cache;
	}


	/**
	 * @since 25.0.0
	 */
	private function garbageCollect(): void {
		while (count($this->cache) > $this->capacity) {
			reset($this->cache);
			$key = key($this->cache);
			$this->remove($key);
		}
	}

	/**
	 * @inheritdoc
	 * @since 25.0.0
	 */
	public static function isAvailable(): bool {
		return true;
	}
}
