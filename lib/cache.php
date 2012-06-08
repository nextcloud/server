<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Cache {
	static protected $cache;

	static protected function init() {
		$fast_cache = null;
		if (!$fast_cache && function_exists('xcache_set')) {
			$fast_cache = new OC_Cache_XCache();
		}
		if (!$fast_cache && function_exists('apc_store')) {
			$fast_cache = new OC_Cache_APC();
		}
		self::$cache = new OC_Cache_File();
		if ($fast_cache) {
			self::$cache = new OC_Cache_Broker($fast_cache, self::$cache);
		}
	}

	static public function get($key) {
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->get($key);
	}

	static public function set($key, $value, $ttl=0) {
		if (empty($key)) {
			return false;
		}
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->set($key, $value, $ttl);
	}

	static public function hasKey($key) {
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->hasKey($key);
	}

	static public function remove($key) {
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->remove($key);
	}

	static public function clear() {
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->clear();
	}

}
