<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Memcache;

use OC\Memcache\Factory;
use OC\Memcache\NullCache;
use OCP\HintException;
use OCP\Profiler\IProfiler;
use Psr\Log\LoggerInterface;

class Test_Factory_Available_Cache1 extends NullCache {
	public function __construct($prefix = '') {
	}

	public static function isAvailable(): bool {
		return true;
	}
}

class Test_Factory_Available_Cache2 extends NullCache {
	public function __construct($prefix = '') {
	}

	public static function isAvailable(): bool {
		return true;
	}
}

class Test_Factory_Unavailable_Cache1 extends NullCache {
	public function __construct($prefix = '') {
	}

	public static function isAvailable(): bool {
		return false;
	}
}

class Test_Factory_Unavailable_Cache2 extends NullCache {
	public function __construct($prefix = '') {
	}

	public static function isAvailable(): bool {
		return false;
	}
}

/**
 * @group Memcache
 */
class FactoryTest extends \Test\TestCase {
	public const AVAILABLE1 = '\\Test\\Memcache\\Test_Factory_Available_Cache1';
	public const AVAILABLE2 = '\\Test\\Memcache\\Test_Factory_Available_Cache2';
	public const UNAVAILABLE1 = '\\Test\\Memcache\\Test_Factory_Unavailable_Cache1';
	public const UNAVAILABLE2 = '\\Test\\Memcache\\Test_Factory_Unavailable_Cache2';

	public static function cacheAvailabilityProvider(): array {
		return [
			[
				// local and distributed available
				self::AVAILABLE1, self::AVAILABLE2, null,
				self::AVAILABLE1, self::AVAILABLE2, Factory::NULL_CACHE
			],
			[
				// local and distributed null
				null, null, null,
				Factory::NULL_CACHE, Factory::NULL_CACHE, Factory::NULL_CACHE
			],
			[
				// local available, distributed null (most common scenario)
				self::AVAILABLE1, null, null,
				self::AVAILABLE1, self::AVAILABLE1, Factory::NULL_CACHE
			],
			[
				// locking cache available
				null, null, self::AVAILABLE1,
				Factory::NULL_CACHE, Factory::NULL_CACHE, self::AVAILABLE1
			],
			[
				// locking cache unavailable: no exception here in the factory
				null, null, self::UNAVAILABLE1,
				Factory::NULL_CACHE, Factory::NULL_CACHE, Factory::NULL_CACHE
			]
		];
	}

	public static function cacheUnavailableProvider(): array {
		return [
			[
				// local available, distributed unavailable
				self::AVAILABLE1, self::UNAVAILABLE1
			],
			[
				// local unavailable, distributed available
				self::UNAVAILABLE1, self::AVAILABLE1
			],
			[
				// local and distributed unavailable
				self::UNAVAILABLE1, self::UNAVAILABLE2
			],
		];
	}

	/**
	 * @dataProvider cacheAvailabilityProvider
	 */
	public function testCacheAvailability($localCache, $distributedCache, $lockingCache,
		$expectedLocalCache, $expectedDistributedCache, $expectedLockingCache): void {
		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$profiler = $this->getMockBuilder(IProfiler::class)->getMock();
		$factory = new Factory(fn () => 'abc', $logger, $profiler, $localCache, $distributedCache, $lockingCache);
		$this->assertTrue(is_a($factory->createLocal(), $expectedLocalCache));
		$this->assertTrue(is_a($factory->createDistributed(), $expectedDistributedCache));
		$this->assertTrue(is_a($factory->createLocking(), $expectedLockingCache));
	}

	/**
	 * @dataProvider cacheUnavailableProvider
	 */
	public function testCacheNotAvailableException($localCache, $distributedCache): void {
		$this->expectException(HintException::class);

		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$profiler = $this->getMockBuilder(IProfiler::class)->getMock();
		new Factory(fn () => 'abc', $logger, $profiler, $localCache, $distributedCache);
	}

	public function testCreateInMemory(): void {
		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$profiler = $this->getMockBuilder(IProfiler::class)->getMock();
		$factory = new Factory(fn () => 'abc', $logger, $profiler, null, null, null);

		$cache = $factory->createInMemory();
		$cache->set('test', 48);

		self::assertSame(48, $cache->get('test'));
	}
}
