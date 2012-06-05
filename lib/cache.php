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
		self::$cache = new OC_Cache_File();
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

	static public function remove($key) {
		if (!self::$cache) {
			self::init();
		}
		return self::$cache->remove($key);
	}

}
