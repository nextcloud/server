<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Memcache;

use OCP\IMemcache;

class NullCache extends Cache implements IMemcache {
	#[\Override]
	public function get($key) {
		return null;
	}

	#[\Override]
	public function set($key, $value, $ttl = 0) {
		return true;
	}

	#[\Override]
	public function hasKey($key) {
		return false;
	}

	#[\Override]
	public function remove($key) {
		return true;
	}

	#[\Override]
	public function add($key, $value, $ttl = 0) {
		return true;
	}

	#[\Override]
	public function inc($key, $step = 1) {
		return true;
	}

	#[\Override]
	public function dec($key, $step = 1) {
		return true;
	}

	#[\Override]
	public function cas($key, $old, $new) {
		return true;
	}

	#[\Override]
	public function cad($key, $old) {
		return true;
	}

	#[\Override]
	public function ncad(string $key, mixed $old): bool {
		return true;
	}


	#[\Override]
	public function clear($prefix = '') {
		return true;
	}

	#[\Override]
	public static function isAvailable(): bool {
		return true;
	}
}
