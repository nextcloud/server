<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Lock;

use OC\Lock\MemcacheLockingProvider;
use OC\Memcache\ArrayCache;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IMemcache;
use OCP\IMemcacheTTL;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;

class MemcacheLockingProviderTest extends LockingProvider {
	/**
	 * @var IMemcache
	 */
	private $memcache;

	private int $now = 0;

	/**
	 * @return ILockingProvider
	 */
	#[\Override]
	protected function getInstance() {
		$this->memcache = new ArrayCache();
		$timeProvider = Server::get(ITimeFactory::class);
		return new MemcacheLockingProvider($this->memcache, $timeProvider);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->memcache->clear();
		parent::tearDown();
	}

	public function testFailedExclusiveLockRestoresTTLAfterNestedSharedLock(): void {
		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')
			->willReturnCallback(fn () => $this->now);
		$memcache = new class($timeFactory) extends ArrayCache implements IMemcacheTTL {
			private array $expires = [];

			public function __construct(
				private ITimeFactory $timeFactory,
			) {
				parent::__construct();
			}

			#[\Override]
			public function setTTL(string $key, int $ttl) {
				$this->expires[$key] = $this->timeFactory->getTime() + $ttl;
			}

			#[\Override]
			public function getTTL(string $key): int|false {
				return isset($this->expires[$key]) ? $this->expires[$key] - $this->timeFactory->getTime() : false;
			}

			#[\Override]
			public function compareSetTTL(string $key, $value, int $ttl): bool {
				if ($this->get($key) !== $value) {
					return false;
				}
				$this->setTTL($key, $ttl);
				return true;
			}
		};

		$this->now = 1000;
		$leaked = new MemcacheLockingProvider($memcache, $timeFactory);
		$leaked->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->now = 2000;
		$provider = new MemcacheLockingProvider($memcache, $timeFactory);
		$provider->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$provider->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$provider->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		try {
			$provider->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
			$this->fail('Expected LockedException');
		} catch (LockedException) {
		}
		$provider->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(2600, $memcache->getTTL('foo'));
	}
}
