<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class Factory {
	/**
	 * get a cache instance, will return null if no backend is available
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	function create($prefix = '') {
		if (XCache::isAvailable()) {
			return new XCache($prefix);
		} elseif (APCu::isAvailable()) {
			return new APCu($prefix);
		} elseif (APC::isAvailable()) {
			return new APC($prefix);
		} elseif (Memcached::isAvailable()) {
			return new Memcached($prefix);
		} else {
			return null;
		}
	}

	/**
	 * check if there is a memcache backend available
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable() || Memcached::isAvailable();
	}

	/**
	 * get a in-server cache instance, will return null if no backend is available
	 *
	 * @param string $prefix
	 * @return \OC\Memcache\Cache
	 */
	public static function createLowLatency($prefix = '') {
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
	public static function isAvailableLowLatency() {
		return XCache::isAvailable() || APCu::isAvailable() || APC::isAvailable();
	}


}
