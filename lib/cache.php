<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Cache {
	/**
	 * @var OC_Cache $user_cache
	 */
	static protected $user_cache;
	/**
	 * @var OC_Cache $global_cache
	 */
	static protected $global_cache;
	static protected $isFast=null;

	/**
	 * get the global cache
	 * @return OC_Cache
	 */
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

	/**
	 * get the user cache
	 * @return OC_Cache
	 */
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

	/**
	 * get a value from the user cache
	 * @return mixed
	 */
	static public function get($key) {
		$user_cache = self::getUserCache();
		return $user_cache->get($key);
	}

	/**
	 * set a value in the user cache
	 * @return bool
	 */
	static public function set($key, $value, $ttl=0) {
		if (empty($key)) {
			return false;
		}
		$user_cache = self::getUserCache();
		return $user_cache->set($key, $value, $ttl);
	}

	/**
	 * check if a value is set in the user cache
	 * @return bool
	 */
	static public function hasKey($key) {
		$user_cache = self::getUserCache();
		return $user_cache->hasKey($key);
	}

	/**
	 * remove an item from the user cache
	 * @return bool
	 */
	static public function remove($key) {
		$user_cache = self::getUserCache();
		return $user_cache->remove($key);
	}

	/**
	 * clear the user cache
	 * @return bool
	 */
	static public function clear() {
		$user_cache = self::getUserCache();
		return $user_cache->clear();
	}

	/**
	 * check if a fast memory based cache is available
	 * @return true
	 */
	static public function isFast() {
		if(is_null(self::$isFast)){
			self::$isFast=function_exists('xcache_set') || function_exists('apc_store');
		}
		return self::$isFast;
	}

}
