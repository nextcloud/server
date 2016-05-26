<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

use OCP\IMemcache;

/**
 * See http://xcache.lighttpd.net/wiki/XcacheApi for provided constants and
 * functions etc.
 */
class XCache extends Cache implements IMemcache {
	use CASTrait;

	use CADTrait;

	/**
	 * entries in XCache gets namespaced to prevent collisions between ownCloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		return xcache_get($this->getNamespace() . $key);
	}

	public function set($key, $value, $ttl = 0) {
		if ($ttl > 0) {
			return xcache_set($this->getNamespace() . $key, $value, $ttl);
		} else {
			return xcache_set($this->getNamespace() . $key, $value);
		}
	}

	public function hasKey($key) {
		return xcache_isset($this->getNamespace() . $key);
	}

	public function remove($key) {
		return xcache_unset($this->getNamespace() . $key);
	}

	public function clear($prefix = '') {
		if (function_exists('xcache_unset_by_prefix')) {
			return xcache_unset_by_prefix($this->getNamespace() . $prefix);
		} else {
			// Since we can not clear by prefix, we just clear the whole cache.
			xcache_clear_cache(\XC_TYPE_VAR, 0);
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
		return xcache_inc($this->getPrefix() . $key, $step);
	}

	/**
	 * Decrease a stored number
	 *
	 * @param string $key
	 * @param int $step
	 * @return int | bool
	 */
	public function dec($key, $step = 1) {
		return xcache_dec($this->getPrefix() . $key, $step);
	}

	static public function isAvailable() {
		if (!extension_loaded('xcache')) {
			return false;
		}
		if (\OC::$CLI && !getenv('XCACHE_TEST')) {
			return false;
		}
		if (!function_exists('xcache_unset_by_prefix') && \OC::$server->getIniWrapper()->getBool('xcache.admin.enable_auth')) {
			// We do not want to use XCache if we can not clear it without
			// using the administration function xcache_clear_cache()
			// AND administration functions are password-protected.
			return false;
		}
		$var_size = \OC::$server->getIniWrapper()->getBytes('xcache.var_size');
		if (!$var_size) {
			return false;
		}
		return true;
	}
}
