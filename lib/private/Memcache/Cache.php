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
	#[\Override]
	abstract public function get($key);

	/**
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
	 * @return mixed
	 */
	#[\Override]
	abstract public function set($key, $value, $ttl = 0);

	/**
	 * @param string $key
	 * @return mixed
	 */
	#[\Override]
	abstract public function hasKey($key);

	/**
	 * @param string $key
	 * @return mixed
	 */
	#[\Override]
	abstract public function remove($key);

	/**
	 * @param string $prefix
	 * @return mixed
	 */
	#[\Override]
	abstract public function clear($prefix = '');

	//implement the ArrayAccess interface

	#[\Override]
	public function offsetExists($offset): bool {
		return $this->hasKey($offset);
	}

	#[\Override]
	public function offsetSet($offset, $value): void {
		$this->set($offset, $value);
	}

	/**
	 * @return mixed
	 */
	#[\Override]
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	#[\Override]
	public function offsetUnset($offset): void {
		$this->remove($offset);
	}
}
