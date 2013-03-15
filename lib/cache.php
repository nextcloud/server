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
	/**
	 * @var OC_Cache $global_cache_fast
	 */
	static protected $global_cache_fast;
	/**
	 * @var OC_Cache $user_cache_fast
	 */
	static protected $user_cache_fast;
	static protected $isFast=null;

	/**
	 * get the global cache
	 * @return OC_Cache
	 */
	static public function getGlobalCache($fast=false) {
		if (!self::$global_cache) {
			self::$global_cache_fast = null;
			if (!self::$global_cache_fast && function_exists('xcache_set')) {
				self::$global_cache_fast = new OC_Cache_XCache(true);
			}
			if (!self::$global_cache_fast && function_exists('apc_store')) {
				self::$global_cache_fast = new OC_Cache_APC(true);
			}

			self::$global_cache = new OC_Cache_FileGlobal();
			if (self::$global_cache_fast) {
				self::$global_cache = new OC_Cache_Broker(self::$global_cache_fast, self::$global_cache);
			}
		}
		if($fast) {
			if(self::$global_cache_fast) {
				return self::$global_cache_fast;
			}else{
				return false;
			}
		}
		return self::$global_cache;
	}

	/**
	 * get the user cache
	 * @return OC_Cache
	 */
	static public function getUserCache($fast=false) {
		if (!self::$user_cache) {
			self::$user_cache_fast = null;
			if (!self::$user_cache_fast && function_exists('xcache_set')) {
				self::$user_cache_fast = new OC_Cache_XCache();
			}
			if (!self::$user_cache_fast && function_exists('apc_store')) {
				self::$user_cache_fast = new OC_Cache_APC();
			}

			self::$user_cache = new OC_Cache_File();
			if (self::$user_cache_fast) {
				self::$user_cache = new OC_Cache_Broker(self::$user_cache_fast, self::$user_cache);
			}
		}

		if($fast) {
			if(self::$user_cache_fast) {
				return self::$user_cache_fast;
			}else{
				return false;
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
	 * clear the user cache of all entries starting with a prefix
	 * @param string prefix (optional)
	 * @return bool
	 */
	static public function clear($prefix='') {
		$user_cache = self::getUserCache();
		return $user_cache->clear($prefix);
	}

	/**
	 * check if a fast memory based cache is available
	 * @return true
	 */
	static public function isFast() {
		if(is_null(self::$isFast)) {
			self::$isFast=function_exists('xcache_set') || function_exists('apc_store');
		}
		return self::$isFast;
	}

	static public function generateCacheKeyFromFiles($files) {
		$key = '';
		sort($files);
		foreach($files as $file) {
			$stat = stat($file);
			$key .= $file.$stat['mtime'].$stat['size'];
		}
		return md5($key);
	}
}
