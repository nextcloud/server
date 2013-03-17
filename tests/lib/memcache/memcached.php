<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Memcache_Memcached extends Test_Cache {
	public function setUp() {
		if (!\OC\Memcache\Memcached::isAvailable()) {
			$this->markTestSkipped('The memcached extension is not available.');
			return;
		}
		$this->instance = new \OC\Memcache\Memcached();
	}
}
