<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class Cache {
	/**
	 * @var Cache $user_cache
	 */
	static protected $user_cache;
	/**
	 * @var Cache $global_cache
	 */
	static protected $global_cache;

	/**
	 * get the global cache
	 * @return Cache
	 */
	static public function getGlobalCache() {
		if (!self::$global_cache) {
			self::$global_cache = new Cache\FileGlobal();
		}
		return self::$global_cache;
	}

	/**
	 * get the user cache
	 * @return Cache
	 */
	static public function getUserCache() {
		if (!self::$user_cache) {
			self::$user_cache = new Cache\File();
		}
		return self::$user_cache;
	}

	/**
	 * get a value from the user cache
	 * @param string $key
	 * @return mixed
	 */
	static public function get($key) {
		$user_cache = self::getUserCache();
		return $user_cache->get($key);
	}

	/**
	 * set a value in the user cache
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl
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
	 * @param string $key
	 * @return bool
	 */
	static public function hasKey($key) {
		$user_cache = self::getUserCache();
		return $user_cache->hasKey($key);
	}

	/**
	 * remove an item from the user cache
	 * @param string $key
	 * @return bool
	 */
	static public function remove($key) {
		$user_cache = self::getUserCache();
		return $user_cache->remove($key);
	}

	/**
	 * clear the user cache of all entries starting with a prefix
	 * @param string $prefix (optional)
	 * @return bool
	 */
	static public function clear($prefix='') {
		$user_cache = self::getUserCache();
		return $user_cache->clear($prefix);
	}

	/**
	 * creates cache key based on the files given
	 * @param string[] $files
	 * @return string
	 */
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
