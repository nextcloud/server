<?php
/**
 * Copyright (c) 2015 Robin McCorkell <rmccorkell@karoshi.org.uk>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class Null extends Cache {
	public function get($key) {
		return null;
	}

	public function set($key, $value, $ttl = 0) {
		return true;
	}

	public function hasKey($key) {
		return false;
	}

	public function remove($key) {
		return true;
	}

	public function clear($prefix = '') {
		return true;
	}

	static public function isAvailable() {
		return true;
	}
}
