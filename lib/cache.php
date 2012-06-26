<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Cache {
	static protected $user_cache;
	static protected $global_cache;

	static public function getGlobalCache() {
		if (!self::$global_cache) {
			$fast_cache = null;
			if (!$fast_cache && function_exists('xcache_set')) {
				$fast_cache = new OC_Cache_XCache(true);
			}
			if (!$fast_cache && function_exists('apc_store')) {
				$fast_cache = new OC_Cache_APC(true);
			}
			self::$global_cache = new OC_Cache_FileGlobal();
			if ($fast_cache) {
				self::$global_cache = new OC_Cache_Broker($fast_cache, self::$global_cache);
			}
		}
		return self::$global_cache;
	}

	static public function getUserCache() {
		if (!self::$user_cache) {
			$fast_cache = null;
			if (!$fast_cache && function_exists('xcache_set')) {
				$fast_cache = new OC_Cache_XCache();
			}
			if (!$fast_cache && function_exists('apc_store')) {
				$fast_cache = new OC_Cache_APC();
			}
			self::$user_cache = new OC_Cache_File();
			if ($fast_cache) {
				self::$user_cache = new OC_Cache_Broker($fast_cache, self::$user_cache);
			}
		}
		return self::$user_cache;
	}

	static public function get($key) {
		$user_cache = self::getUserCache();
		return $user_cache->get($key);
	}

	static public function set($key, $value, $ttl=0) {
		if (empty($key)) {
			return false;
		}
		$user_cache = self::getUserCache();
		return $user_cache->set($key, $value, $ttl);
	}

	static public function hasKey($key) {
		$user_cache = self::getUserCache();
		return $user_cache->hasKey($key);
	}

	static public function remove($key) {
		$user_cache = self::getUserCache();
		return $user_cache->remove($key);
	}

	static public function clear() {
		$user_cache = self::getUserCache();
		return $user_cache->clear();
	}

}
