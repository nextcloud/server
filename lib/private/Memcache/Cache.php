<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

/**
 * @template-implements \ArrayAccess<string,mixed>
 */
abstract class Cache implements \ArrayAccess, \OCP\ICache {
	/**
	 * @var string $prefix
	 */
	protected $prefix;

	/**
	 * @param string $prefix
	 */
	public function __construct($prefix = '') {
		$this->prefix = $prefix;
	}

	/**
	 * @return string Prefix used for caching purposes
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	abstract public function get($key);

	abstract public function set($key, $value, $ttl = 0);

	abstract public function hasKey($key);

	abstract public function remove($key);

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
