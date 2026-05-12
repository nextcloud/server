<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcache;

class Memcached extends Cache implements IMemcache {
	use CASTrait;

	private \Memcached $cache;

	use CADTrait;

	public function __construct($prefix = '') {
		parent::__construct($prefix);
		$this->cache = \OCP\Server::get(MemcachedFactory::class)->getInstance();
	}

	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	#[\Override]
	public function get($key) {
		$result = $this->cache->get($this->getNameSpace() . $key);
		if ($result === false && $this->cache->getResultCode() === \Memcached::RES_NOTFOUND) {
			return null;
		} else {
			return $result;
		}
	}

	#[\Override]
	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			$result = $this->cache->set($this->getNameSpace() . $key, $value, $ttl);
		} else {
			$result = $this->cache->set($this->getNameSpace() . $key, $value);
		}
		return $result || $this->isSuccess();
	}

	#[\Override]
	public function hasKey($key) {
		$this->cache->get($this->getNameSpace() . $key);
		return $this->cache->getResultCode() === \Memcached::RES_SUCCESS;
	}

	#[\Override]
	public function remove($key) {
		$result = $this->cache->delete($this->getNameSpace() . $key);
		return $result || $this->isSuccess() || $this->cache->getResultCode() === \Memcached::RES_NOTFOUND;
	}

	#[\Override]
	public function clear($prefix = '') {
		// Newer Memcached doesn't like getAllKeys(), flush everything
		$this->cache->flush();
		return true;
	}

	/**
	 * Set a value in the cache if it's not already stored
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl Time To Live in seconds. Defaults to 60*60*24
	 * @return bool
	 */
	#[\Override]
	public function add($key, $value, $ttl = 0) {
		$result = $this->cache->add($this->getPrefix() . $key, $value, $ttl);
		return $result || $this->isSuccess();
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	#[\Override]
	public function inc($key, $step = 1) {
		$this->add($key, 0);
		$result = $this->cache->increment($this->getPrefix() . $key, $step);

		if ($this->cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	#[\Override]
	public function dec($key, $step = 1) {
		$result = $this->cache->decrement($this->getPrefix() . $key, $step);

		if ($this->cache->getResultCode() !== \Memcached::RES_SUCCESS) {
			return false;
		}

		return $result;
	}

	#[\Override]
	public static function isAvailable(): bool {
		return extension_loaded('memcached');
	}

	private function isSuccess(): bool {
		return $this->cache->getResultCode() === \Memcached::RES_SUCCESS;
	}
}
