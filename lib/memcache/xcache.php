<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class XCache extends Cache {
	/**
	 * entries in XCache gets namespaced to prevent collisions between owncloud instances and users
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
		xcache_unset_by_prefix($this->getNamespace().$prefix);
		return true;
	}

	static public function isAvailable(){
		if (!extension_loaded('xcache')) {
			return false;
		} elseif (\OC::$CLI) {
			return false;
		}else{
			return true;
		}
	}
}

if(!function_exists('xcache_unset_by_prefix')) {
	function xcache_unset_by_prefix($prefix) {
		// Since we can't clear targetted cache, we'll clear all. :(
		xcache_clear_cache(\XC_TYPE_VAR, 0);
	}
}
