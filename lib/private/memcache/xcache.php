<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

/**
 * See http://xcache.lighttpd.net/wiki/XcacheApi for provided constants and
 * functions etc.
 */
class XCache extends Cache {
	/**
	 * entries in XCache gets namespaced to prevent collisions between ownCloud instances and users
	 */
	protected function getNameSpace() {
		return $this->prefix;
	}

	public function get($key) {
		return xcache_get($this->getNamespace().$key);
	}

	public function set($key, $value, $ttl=0) {
		if($ttl>0) {
			return xcache_set($this->getNamespace().$key, $value, $ttl);
		}else{
			return xcache_set($this->getNamespace().$key, $value);
		}
	}

	public function hasKey($key) {
		return xcache_isset($this->getNamespace().$key);
	}

	public function remove($key) {
		return xcache_unset($this->getNamespace().$key);
	}

	public function clear($prefix='') {
		if (function_exists('xcache_unset_by_prefix')) {
			return xcache_unset_by_prefix($this->getNamespace().$prefix);
		} else {
			// Since we can not clear by prefix, we just clear the whole cache.
			xcache_clear_cache(\XC_TYPE_VAR, 0);
		}
		return true;
	}

	static public function isAvailable(){
		if (!extension_loaded('xcache')) {
			return false;
		}
		if (\OC::$CLI) {
			return false;
		}
		if (!function_exists('xcache_unset_by_prefix') && ini_get('xcache.admin.enable_auth')) {
			// We do not want to use XCache if we can not clear it without
			// using the administration function xcache_clear_cache()
			// AND administration functions are password-protected.
			return false;
		}
		$var_size = (int) ini_get('xcache.var_size');
		if (!$var_size) {
			return false;
		}
		return true;
	}
}
