<?php

namespace OC\Memcache;

use OCP\Cache\CappedMemoryCache;
use OCP\ICache;

/**
 * Wrap a cache instance with an extra later of local, in-memory caching
 */
class WithLocalCache implements ICache {
	private ICache $inner;
	private CappedMemoryCache $cached;

	public function __construct(ICache $inner, int $localCapacity = 512) {
		$this->inner = $inner;
		$this->cached = new CappedMemoryCache($localCapacity);
	}

	public function get($key) {
		if (isset($this->cached[$key])) {
			return $this->cached[$key];
		} else {
			$value = $this->inner->get($key);
			if (!is_null($value)) {
				$this->cached[$key] = $value;
			}
			return $value;
		}
	}

	public function set($key, $value, $ttl = 0) {
		$this->cached[$key] = $value;
		return $this->inner->set($key, $value, $ttl);
	}

	public function hasKey($key) {
		return isset($this->cached[$key]) || $this->inner->hasKey($key);
	}

	public function remove($key) {
		unset($this->cached[$key]);
		return $this->inner->remove($key);
	}

	public function clear($prefix = '') {
		$this->cached->clear();
		return $this->inner->clear($prefix);
	}

	public static function isAvailable(): bool {
		return false;
	}
}
