<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

class Memcached extends Cache {
	static public function setUpBeforeClass() {
		parent::setUpBeforeClass();

		if (!\OC\Memcache\Memcached::isAvailable()) {
			self::markTestSkipped('The memcached extension is not available.');
		}
		$instance = new \OC\Memcache\Memcached(self::getUniqueID());
		if ($instance->set(self::getUniqueID(), self::getUniqueID()) === false) {
			self::markTestSkipped('memcached server seems to be down.');
		}
	}

	protected function setUp() {
		parent::setUp();
		$this->instance = new \OC\Memcache\Memcached($this->getUniqueID());
	}
}
