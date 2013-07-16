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
	 * @param bool $global
	 * @return \OC\Memcache\Cache
	 */
	function create($global = false) {
		if (XCache::isAvailable()) {
			return new XCache($global);
		} elseif (APC::isAvailable()) {
			return new APC($global);
		} elseif (Memcached::isAvailable()) {
			return new Memcached($global);
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
		return XCache::isAvailable() || APC::isAvailable() || Memcached::isAvailable();
	}
}
