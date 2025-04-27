<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcache;

class ArrayCache extends Cache implements IMemcache {
	/** @var array Array with the cached data */
	protected $cachedData = [];

	use CADTrait;

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
			if (str_starts_with($key, $prefix)) {
				$this->remove($key);
			}
		}
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
	public function add($key, $value, $ttl = 0) {
		// since this cache is not shared race conditions aren't an issue
		if ($this->hasKey($key)) {
			return false;
		} else {
			return $this->set($key, $value, $ttl);
		}
	}

	/**
	 * Increase a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function inc($key, $step = 1) {
		$oldValue = $this->get($key);
		if (is_int($oldValue)) {
			$this->set($key, $oldValue + $step);
			return $oldValue + $step;
		} else {
			$success = $this->add($key, $step);
			return $success ? $step : false;
		}
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		$oldValue = $this->get($key);
		if (is_int($oldValue)) {
			$this->set($key, $oldValue - $step);
			return $oldValue - $step;
		} else {
			return false;
		}
	}

	/**
	 * Compare and set
	 *
	 * @param string $key
	 * @param mixed $old
	 * @param mixed $new
	 * @return bool
	 */
	public function cas($key, $old, $new) {
		if ($this->get($key) === $old) {
			return $this->set($key, $new);
		} else {
			return false;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public static function isAvailable(): bool {
		return true;
	}
}
