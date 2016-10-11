<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

class XCacheTest extends Cache {
	protected function setUp() {
		parent::setUp();

		if (!\OC\Memcache\XCache::isAvailable()) {
			$this->markTestSkipped('The xcache extension is not available.');
			return;
		}
		$this->instance = new \OC\Memcache\XCache($this->getUniqueID());
	}
}
