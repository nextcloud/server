<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Lock;

use OC\Lock\DBLockingProvider;
use OCP\IDBConnection;
use OCP\Lock\ILockingProvider;
use OCP\Server;

/**
 * @group DB
 *
 * @package Test\Lock
 */
class NonCachingDBLockingProviderTest extends DBLockingProviderTest {
	/**
	 * @return \OCP\Lock\ILockingProvider
	 */
	protected function getInstance() {
		$this->connection = Server::get(IDBConnection::class);
		return new DBLockingProvider($this->connection, $this->timeFactory, 3600, false);
	}

	public function testDoubleShared(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(2, $this->getLockValue('foo'));

		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(1, $this->getLockValue('foo'));

		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->assertEquals(0, $this->getLockValue('foo'));
	}
}
