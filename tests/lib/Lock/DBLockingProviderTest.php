<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Lock;

use OC\Lock\DBLockingProvider;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OCP\Server;

/**
 * Class DBLockingProvider
 *
 *
 * @package Test\Lock
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class DBLockingProviderTest extends LockingProvider {
	/**
	 * @var \OC\Lock\DBLockingProvider
	 */
	protected $instance;

	/**
	 * @var IDBConnection
	 */
	protected $connection;

	/**
	 * @var ITimeFactory
	 */
	protected $timeFactory;

	protected $currentTime;

	protected function setUp(): void {
		$this->currentTime = time();
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturnCallback(function () {
				return $this->currentTime;
			});
		parent::setUp();
	}

	/**
	 * @return ILockingProvider
	 */
	protected function getInstance() {
		$this->connection = Server::get(IDBConnection::class);
		return new DBLockingProvider($this->connection, $this->timeFactory, 3600);
	}

	protected function tearDown(): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete('file_locks')->executeStatement();
		parent::tearDown();
	}

	public function testCleanEmptyLocks(): void {
		$this->currentTime = 100;
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->instance->acquireLock('asd', ILockingProvider::LOCK_EXCLUSIVE);

		$this->currentTime = 200;
		$this->instance->acquireLock('bar', ILockingProvider::LOCK_EXCLUSIVE);
		$this->instance->changeLock('asd', ILockingProvider::LOCK_SHARED);

		$this->currentTime = 150 + 3600;

		$this->assertEquals(3, $this->getLockEntryCount());

		$this->instance->cleanExpiredLocks();

		$this->assertEquals(2, $this->getLockEntryCount());
	}

	private function getLockEntryCount(): int {
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select($qb->func()->count('*'))
			->from('file_locks')
			->executeQuery();
		return (int)$result->fetchOne();
	}

	protected function getLockValue($key) {
		$query = $this->connection->getQueryBuilder();
		$query->select('lock')
			->from('file_locks')
			->where($query->expr()->eq('key', $query->createNamedParameter($key)));

		$result = $query->executeQuery();
		$rows = $result->fetchOne();
		$result->closeCursor();

		return $rows;
	}

	public function testDoubleShared(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(1, $this->getLockValue('foo'));

		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(1, $this->getLockValue('foo'));

		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(1, $this->getLockValue('foo'));

		$this->instance->releaseAll();

		$this->assertEquals(0, $this->getLockValue('foo'));
	}
}
