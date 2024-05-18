<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Memcache;

use OC\Memcache\Redis;

/**
 * @group Memcache
 * @group Redis
 */
class RedisTest extends Cache {
	/**
	 * @var Redis cache;
	 */
	protected $instance;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if (!\OC\Memcache\Redis::isAvailable()) {
			self::markTestSkipped('The redis extension is not available.');
		}

		if (\OC::$server->getConfig()->getSystemValue('redis', []) === []) {
			self::markTestSkipped('Redis not configured in config.php');
		}

		$errorOccurred = false;
		set_error_handler(
			function ($errno, $errstr) {
				throw new \RuntimeException($errstr, 123456789);
			},
			E_WARNING
		);
		$instance = null;
		try {
			$instance = new \OC\Memcache\Redis(self::getUniqueID());
		} catch (\RuntimeException $e) {
			$errorOccurred = $e->getCode() === 123456789 ? $e->getMessage() : false;
		}
		restore_error_handler();
		if ($errorOccurred !== false) {
			self::markTestSkipped($errorOccurred);
		}

		if ($instance === null) {
			throw new \Exception('redis server is not reachable');
		}

		if ($instance->set(self::getUniqueID(), self::getUniqueID()) === false) {
			self::markTestSkipped('redis server seems to be down.');
		}
	}

	protected function setUp(): void {
		parent::setUp();
		$this->instance = new \OC\Memcache\Redis($this->getUniqueID());
	}

	public function testScriptHashes() {
		foreach (\OC\Memcache\Redis::LUA_SCRIPTS as $script) {
			$this->assertEquals(sha1($script[0]), $script[1]);
		}
	}

	public function testCasTtlNotChanged() {
		$this->instance->set('foo', 'bar', 50);
		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 100 - $this->instance->getTTL('foo'));
	}

	public function testCasTtlChanged() {
		$this->instance->set('foo', 'bar1', 50);
		$this->assertFalse($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 50 - $this->instance->getTTL('foo'));
	}
}
