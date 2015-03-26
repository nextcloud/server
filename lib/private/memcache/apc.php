<?php
/**
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Otto Sabart <ottosabart@seberm.com>
 * @author Robin Appelman <icewind@owncloud.com>
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

class APC extends Cache {
	public function get($key) {
		$result = apc_fetch($this->getPrefix() . $key, $success);
		if (!$success) {
			return null;
		}
		return $result;
	}

	public function set($key, $value, $ttl = 0) {
		return apc_store($this->getPrefix() . $key, $value, $ttl);
	}

	public function hasKey($key) {
		return apc_exists($this->getPrefix() . $key);
	}

	public function remove($key) {
		return apc_delete($this->getPrefix() . $key);
	}

	public function clear($prefix = '') {
		$ns = $this->getPrefix() . $prefix;
		$ns = preg_quote($ns, '/');
		$iter = new \APCIterator('user', '/^' . $ns . '/', APC_ITER_KEY);
		return apc_delete($iter);
	}

	static public function isAvailable() {
		if (!extension_loaded('apc')) {
			return false;
		} elseif (!ini_get('apc.enabled')) {
			return false;
		} elseif (!ini_get('apc.enable_cli') && \OC::$CLI) {
			return false;
		} else {
			return true;
		}
	}
}

if (!function_exists('apc_exists')) {
	function apc_exists($keys) {
		$result = false;
		apc_fetch($keys, $result);
		return $result;
	}
}
