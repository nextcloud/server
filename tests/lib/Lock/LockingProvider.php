<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Lock;

use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Test\TestCase;

abstract class LockingProvider extends TestCase {
	/**
	 * @var \OCP\Lock\ILockingProvider
	 */
	protected $instance;

	/**
	 * @return \OCP\Lock\ILockingProvider
	 */
	abstract protected function getInstance();

	protected function setUp(): void {
		parent::setUp();
		$this->instance = $this->getInstance();
	}

	public function testExclusiveLock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testSharedLock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testDoubleSharedLock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
	}

	public function testReleaseSharedLock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
	}

	
	public function testDoubleExclusiveLock(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	public function testReleaseExclusiveLock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	
	public function testExclusiveLockAfterShared(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	public function testExclusiveLockAfterSharedReleased(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAll(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('bar', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('asd', ILockingProvider::LOCK_EXCLUSIVE);
		$this->instance->acquireLock('fizz#A=23', ILockingProvider::LOCK_EXCLUSIVE);

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance->isLocked('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance->isLocked('asd', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance->isLocked('fizz#A=23', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAllAfterChange(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('bar', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('asd', ILockingProvider::LOCK_EXCLUSIVE);

		$this->instance->changeLock('bar', ILockingProvider::LOCK_EXCLUSIVE);

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance->isLocked('bar', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance->isLocked('bar', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertFalse($this->instance->isLocked('asd', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAllAfterUnlock(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('bar', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('asd', ILockingProvider::LOCK_EXCLUSIVE);

		$this->instance->releaseLock('bar', ILockingProvider::LOCK_SHARED);

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertFalse($this->instance->isLocked('asd', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testReleaseAfterReleaseAll(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);

		$this->instance->releaseAll();

		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));

		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);
	}


	
	public function testSharedLockAfterExclusive(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
	}

	public function testLockedExceptionHasPathForShared(): void {
		try {
			$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
			$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
			$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);

			$this->fail('Expected locked exception');
		} catch (LockedException $e) {
			$this->assertEquals('foo', $e->getPath());
		}
	}

	public function testLockedExceptionHasPathForExclusive(): void {
		try {
			$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
			$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
			$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);

			$this->fail('Expected locked exception');
		} catch (LockedException $e) {
			$this->assertEquals('foo', $e->getPath());
		}
	}

	public function testChangeLockToExclusive(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
	}

	public function testChangeLockToShared(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->instance->changeLock('foo', ILockingProvider::LOCK_SHARED);
		$this->assertFalse($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_SHARED));
	}

	
	public function testChangeLockToExclusiveDoubleShared(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	
	public function testChangeLockToExclusiveNoShared(): void {
		$this->expectException(LockedException::class);

		$this->instance->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	
	public function testChangeLockToExclusiveFromExclusive(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->instance->changeLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}

	
	public function testChangeLockToSharedNoExclusive(): void {
		$this->expectException(LockedException::class);

		$this->instance->changeLock('foo', ILockingProvider::LOCK_SHARED);
	}

	
	public function testChangeLockToSharedFromShared(): void {
		$this->expectException(LockedException::class);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->changeLock('foo', ILockingProvider::LOCK_SHARED);
	}

	public function testReleaseNonExistingShared(): void {
		$this->instance->acquireLock('foo', ILockingProvider::LOCK_SHARED);
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		// releasing a lock once to many should not result in a locked state
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_SHARED);

		$this->instance->acquireLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
		$this->assertTrue($this->instance->isLocked('foo', ILockingProvider::LOCK_EXCLUSIVE));
		$this->instance->releaseLock('foo', ILockingProvider::LOCK_EXCLUSIVE);
	}
}
