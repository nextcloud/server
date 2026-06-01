<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\Memcache;

use OC\Memcache\CASTrait;

class CasTraitTestClass {
	use CASTrait;

	// abstract methods from Memcache
	#[\Override]
	public function set($key, $value, $ttl = 0) {
	}
	#[\Override]
	public function get($key) {
	}
	#[\Override]
	public function add($key, $value, $ttl = 0) {
	}
	#[\Override]
	public function remove($key) {
	}
}
