<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

use \OCP\ICacheFactory;

class Factory implements ICacheFactory {
	/**
	 * @var string $globalPrefix
	 */
	private $globalPrefix;

	/**
	 * @param string $globalPrefix
	 */
	public function __construct($globalPrefix) {
		$this->globalPrefix = $globalPrefix;
	}

	/**
	 * get a cache instance, or Null backend if no backend available
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	function create($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		if (XCache::isAvailable()) {
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			return new APC($prefix);
		} elseif (Redis::isAvailable()) {
			return new Redis($prefix);
		} elseif (Memcached::isAvailable()) {
			return new Memcached($prefix);
		} else {
			return new Null($prefix);
		}
	}

	/**
	 * check if there is a memcache backend available
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable() || Redis::isAvailable() || Memcached::isAvailable();
	}

	/**
	 * get a in-server cache instance, will return null if no backend is available
	 *
	 * @param string $prefix
	 * @return null|Cache
	 */
	public function createLowLatency($prefix = '') {
		$prefix = $this->globalPrefix . '/' . $prefix;
		if (XCache::isAvailable()) {
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			return new APC($prefix);
		} else {
			return null;
		}
	}

	/**
	 * check if there is a in-server backend available
	 *
	 * @return bool
	 */
	public function isAvailableLowLatency() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable();
	}


}
