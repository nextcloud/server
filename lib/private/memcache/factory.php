<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Markus Goetz <markus@woboq.com>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
			return new ArrayCache($prefix);
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
