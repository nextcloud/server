<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

class NullCache extends Cache implements \OCP\IMemcache {
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

	public function add($key, $value, $ttl = 0) {
		return true;
	}

	public function inc($key, $step = 1) {
		return true;
	}

	public function dec($key, $step = 1) {
		return true;
	}

	public function cas($key, $old, $new) {
		return true;
	}

	public function cad($key, $old) {
		return true;
	}

	public function ncad(string $key, mixed $old): bool {
		return true;
	}


	public function clear($prefix = '') {
		return true;
	}

	public static function isAvailable(): bool {
		return true;
	}
}
