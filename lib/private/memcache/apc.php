<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
