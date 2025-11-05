<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\CASTrait;

class CasTraitTestClass {
	use CASTrait;

	// abstract methods from Memcache
	public function set($key, $value, $ttl = 0) {
	}
	public function get($key) {
	}
	public function add($key, $value, $ttl = 0) {
	}
	public function remove($key) {
	}
}
