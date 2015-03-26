<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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

namespace OC\Cache;

class Broker {

	/**
	 * @var \OC\Cache
	 */
	protected $fast_cache;

	/**
	 * @var \OC\Cache
	 */
	protected $slow_cache;

	public function __construct($fast_cache, $slow_cache) {
		$this->fast_cache = $fast_cache;
		$this->slow_cache = $slow_cache;
	}

	public function get($key) {
		if ($r = $this->fast_cache->get($key)) {
			return $r;
		}
		return $this->slow_cache->get($key);
	}

	public function set($key, $value, $ttl=0) {
		if (!$this->fast_cache->set($key, $value, $ttl)) {
			if ($this->fast_cache->hasKey($key)) {
				$this->fast_cache->remove($key);
			}
			return $this->slow_cache->set($key, $value, $ttl);
		}
		return true;
	}

	public function hasKey($key) {
		if ($this->fast_cache->hasKey($key)) {
			return true;
		}
		return $this->slow_cache->hasKey($key);
	}

	public function remove($key) {
		if ($this->fast_cache->remove($key)) {
			return true;
		}
		return $this->slow_cache->remove($key);
	}

	public function clear($prefix='') {
		$this->fast_cache->clear($prefix);
		$this->slow_cache->clear($prefix);
	}
}
