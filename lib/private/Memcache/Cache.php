<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\ICache;

/**
 * @template-implements \ArrayAccess<string,mixed>
 */
abstract class Cache implements \ArrayAccess, ICache {
	public function __construct(
		protected string $prefix = '',
	) {
	}

	/**
	 * @return string Prefix used for caching purposes
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	abstract public function get($key);

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return mixed
	 */
	abstract public function set($key, $value, $ttl = 0);

	/**
	 * @param string $key
	 * @return mixed
	 */
	abstract public function hasKey($key);

	/**
	 * @param string $key
	 * @return mixed
	 */
	abstract public function remove($key);

	/**
	 * @param string $prefix
	 * @return mixed
	 */
	abstract public function clear($prefix = '');

	//implement the ArrayAccess interface

	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	public function offsetUnset($offset): void {
		$this->remove($offset);
	}
}
