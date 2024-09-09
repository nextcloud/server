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

	public function get($key) {
		if ($this->hasKey($key)) {
			return $this->cachedData[$key];
		}
		return null;
	}

	public function set($key, $value, $ttl = 0) {
		$this->cachedData[$key] = $value;
		return true;
	}

	public function hasKey($key) {
		return isset($this->cachedData[$key]);
	}

	public function remove($key) {
		unset($this->cachedData[$key]);
		return true;
	}

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

	public function add($key, $value, $ttl = 0) {
		// since this cache is not shared race conditions aren't an issue
		if ($this->hasKey($key)) {
			return false;
		} else {
			return $this->set($key, $value, $ttl);
		}
	}

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

	public function dec($key, $step = 1) {
		$oldValue = $this->get($key);
		if (is_int($oldValue)) {
			$this->set($key, $oldValue - $step);
			return $oldValue - $step;
		} else {
			return false;
		}
	}

	public function cas($key, $old, $new) {
		if ($this->get($key) === $old) {
			return $this->set($key, $new);
		} else {
			return false;
		}
	}

	public static function isAvailable(): bool {
		return true;
	}
}
