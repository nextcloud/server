<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
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
