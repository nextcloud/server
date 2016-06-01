<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

class RedisTest extends Cache {
	static public function setUpBeforeClass() {
		parent::setUpBeforeClass();

		if (!\OC\Memcache\Redis::isAvailable()) {
			self::markTestSkipped('The redis extension is not available.');
		}

		set_error_handler(
			function($errno, $errstr) {
				restore_error_handler();
				self::markTestSkipped($errstr);
			},
			E_WARNING
		);
		$instance = new \OC\Memcache\Redis(self::getUniqueID());
		restore_error_handler();

		if ($instance->set(self::getUniqueID(), self::getUniqueID()) === false) {
			self::markTestSkipped('redis server seems to be down.');
		}
	}

	protected function setUp() {
		parent::setUp();
		$this->instance = new \OC\Memcache\Redis($this->getUniqueID());
	}
}
