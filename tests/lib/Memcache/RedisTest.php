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

	public function testScriptHashes(): void {
		foreach (Redis::LUA_SCRIPTS as $script) {
			$this->assertEquals(sha1($script[0]), $script[1]);
		}
	}

	public function testCasTtlNotChanged(): void {
		$this->instance->set('foo', 'bar', 50);
		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 100 - $this->instance->getTTL('foo'));
	}

	public function testCasTtlChanged(): void {
		$this->instance->set('foo', 'bar1', 50);
		$this->assertFalse($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 50 - $this->instance->getTTL('foo'));
	}

	public function testClearWithPrefixOnlyRemovesMatchingKeys(): void {
		$this->instance->set('foo1', 'a');
		$this->instance->set('foo2', 'b');
		$this->instance->set('bar1', 'c');

		$this->assertTrue($this->instance->clear('foo'));

		$this->assertFalse($this->instance->hasKey('foo1'));
		$this->assertFalse($this->instance->hasKey('foo2'));
		$this->assertTrue($this->instance->hasKey('bar1'));
	}

	public function testClearWithoutMatchesReturnsTrue(): void {
		// Nothing is stored under this prefix; clearing must not error out
		// (regression guard for calling UNLINK/DEL with an empty key list).
		$this->assertTrue($this->instance->clear('no-such-prefix'));
	}

	public function testClearRemovesEntriesAcrossMultipleScanBatches(): void {
		// More keys than a single SCAN batch (self::SCAN_COUNT) to exercise the
		// cursor loop and make sure nothing is left behind.
		$count = 1500;
		for ($i = 0; $i < $count; $i++) {
			$this->instance->set('bulk-' . $i, $i);
		}

		$this->assertTrue($this->instance->clear('bulk-'));

		$this->assertFalse($this->instance->hasKey('bulk-0'));
		$this->assertFalse($this->instance->hasKey('bulk-' . ($count - 1)));
		$this->assertFalse($this->instance->hasKey('bulk-' . intdiv($count, 2)));
	}
}
