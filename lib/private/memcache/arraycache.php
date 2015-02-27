<?php
/**
 * Copyright (c) 2015 Joas Schilling <nickvergessen@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class ArrayCache extends Cache {
	/** @var array Array with the cached data */
	protected $cachedData = array();

	/**
	 * {@inheritDoc}
	 */
	public function get($key) {
		if ($this->hasKey($key)) {
			return $this->cachedData[$key];
		}
		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set($key, $value, $ttl = 0) {
		$this->cachedData[$key] = $value;
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function hasKey($key) {
		return isset($this->cachedData[$key]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove($key) {
		unset($this->cachedData[$key]);
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear($prefix = '') {
		if ($prefix === '') {
			$this->cachedData = [];
			return true;
		}

		foreach ($this->cachedData as $key => $value) {
			if (strpos($key, $prefix) === 0) {
				$this->remove($key);
			}
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	static public function isAvailable() {
		return true;
	}
}
