<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\Redis;
use OCP\IConfig;
use OCP\Server;

#[\PHPUnit\Framework\Attributes\Group('Memcache')]
#[\PHPUnit\Framework\Attributes\Group('Redis')]
class RedisTest extends Cache {
	/**
	 * @var Redis cache;
	 */
	protected $instance;

	#[\Override]
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if (!Redis::isAvailable()) {
			self::markTestSkipped('The redis extension is not available.');
		}

		if (Server::get(IConfig::class)->getSystemValue('redis', []) === []) {
			self::markTestSkipped('Redis not configured in config.php');
		}

		$errorOccurred = false;
		set_error_handler(
			function ($errno, $errstr): void {
				throw new \RuntimeException($errstr, 123456789);
			},
			E_WARNING
		);
		$instance = null;
		try {
			$instance = new Redis(self::getUniqueID());
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

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new Redis($this->getUniqueID());
	}

	private function assertTtlInRange(int $expected, int|false $actual): void {
		$this->assertNotFalse($actual);
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertGreaterThanOrEqual($expected - 1, $actual);
		$this->assertLessThanOrEqual($expected, $actual);
	}
	
	public function testScriptHashes(): void {
		foreach (Redis::LUA_SCRIPTS as $script) {
			$this->assertEquals(sha1($script[0]), $script[1]);
		}
	}

	public function testCompareSetTtlUpdatesTtlWhenValueMatches(): void {
		$this->instance->set('foo', 'bar', 50);

		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', 100));

		$ttl = $this->instance->getTTL('foo');
		$this->assertTtlInRange(100, $ttl);
	}

	public function testCompareSetTtlDoesNotUpdateTtlWhenValueDiffers(): void {
		$this->instance->set('foo', 'bar1', 50);

		$this->assertFalse($this->instance->compareSetTTL('foo', 'bar', 100));

		$ttl = $this->instance->getTTL('foo');
		$this->assertTtlInRange(50, $ttl);
	}

	public function testCompareSetTtlZeroUsesDefaultTtl(): void {
		$this->instance->set('foo', 'bar', 50);

		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', 0));

		$ttl = $this->instance->getTTL('foo');
		$this->assertTtlInRange(Redis::DEFAULT_TTL, $ttl);
	}

	public function testCompareSetTtlIsClampedToMaxTtl(): void {
		$expectedMaxTtl = 30 * 24 * 60 * 60;

		$this->instance->set('foo', 'bar', 50);

		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', $expectedMaxTtl + 1000));

		$ttl = $this->instance->getTTL('foo');
		$this->assertTtlInRange($expectedMaxTtl, $ttl);
	}

	public function testCompareSetTtlNegativeExpiresKeyImmediately(): void {
		$this->instance->set('foo', 'bar', 50);

		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', -1));
		$this->assertFalse($this->instance->hasKey('foo'));
		$this->assertNull($this->instance->get('foo'));
		$this->assertFalse($this->instance->getTTL('foo'));
	}
}
