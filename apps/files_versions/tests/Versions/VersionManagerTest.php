<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Versions\Tests\Versions;

use OC\Files\Storage\Local;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCA\Files_Versions\Versions\IVersion;
use OCA\Files_Versions\Versions\IVersionBackend;
use OCA\Files_Versions\Versions\VersionManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Storage\IStorage;
use OCP\Lock\LockedException;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class VersionManagerTest extends TestCase {
	private function getBackend(bool $shouldUse = true): IVersionBackend {
		$backend = $this->createMock(IVersionBackend::class);
		$backend->method('useBackendForStorage')
			->willReturn($shouldUse);
		return $backend;
	}

	/**
	 * @param class-string<IStorage> $class
	 */
	private function getStorage(string $class): IStorage&MockObject {
		return $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->onlyMethods(array_diff(get_class_methods($class), ['instanceOfStorage']))
			->getMock();
	}

	public function testGetBackendSingle(): void {
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$manager = new VersionManager($dispatcher);
		$backend = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend);

		$this->assertEquals($backend, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendMoreSpecific(): void {
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$manager = new VersionManager($dispatcher);
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);

		$this->assertEquals($backend2, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendNoUse(): void {
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$manager = new VersionManager($dispatcher);
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend(false);
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);

		$this->assertEquals($backend1, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendMultiple(): void {
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$manager = new VersionManager($dispatcher);
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend(false);
		$backend3 = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);
		$manager->registerBackend(Local::class, $backend3);

		$this->assertEquals($backend3, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testRollbackSuccess(): void {
		$versionMock = $this->createMock(IVersion::class);
		$backendMock = $this->createMock(IVersionBackend::class);

		$backendMock->expects($this->once())
			->method('rollback')
			->with($versionMock)
			->willReturn(true);

		$versionMock->method('getBackend')->willReturn($backendMock);

		$dispatcherMock = $this->createMock(IEventDispatcher::class);
		$dispatcherMock->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(VersionRestoredEvent::class));

		$manager = new VersionManager($dispatcherMock);

		$this->assertTrue($manager->rollback($versionMock));
	}

	public function testRollbackNull(): void {
		$versionMock = $this->createMock(IVersion::class);
		$backendMock = $this->createMock(IVersionBackend::class);

		$backendMock->expects($this->once())
			->method('rollback')
			->with($versionMock)
			->willReturn(null);

		$versionMock->method('getBackend')->willReturn($backendMock);

		$dispatcherMock = $this->createMock(IEventDispatcher::class);
		$dispatcherMock->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(VersionRestoredEvent::class));

		$manager = new VersionManager($dispatcherMock);

		$this->assertNull($manager->rollback($versionMock));
	}

	public function testRollbackFailure(): void {
		$versionMock = $this->createMock(IVersion::class);
		$backendMock = $this->createMock(IVersionBackend::class);

		$backendMock->expects($this->once())
			->method('rollback')
			->with($versionMock)
			->willReturn(false);

		$versionMock->method('getBackend')->willReturn($backendMock);

		$dispatcherMock = $this->createMock(IEventDispatcher::class);
		$dispatcherMock->expects($this->never())->method('dispatchTyped');

		$manager = new VersionManager($dispatcherMock);

		$this->assertFalse($manager->rollback($versionMock));
	}

	public function testRollbackRetriesWhileFileIsTransientlyLocked(): void {
		$versionMock = $this->createMock(IVersion::class);
		$backendMock = $this->createMock(IVersionBackend::class);
		$versionMock->method('getBackend')->willReturn($backendMock);

		// The live file is locked for the first two attempts (e.g. a concurrent
		// read holds a shared lock) and then the lock clears.
		$attempts = 0;
		$backendMock->expects($this->exactly(3))
			->method('rollback')
			->with($versionMock)
			->willReturnCallback(function () use (&$attempts): bool {
				$attempts++;
				if ($attempts < 3) {
					throw new LockedException('files/foo.txt');
				}
				return true;
			});

		$dispatcherMock = $this->createMock(IEventDispatcher::class);
		$dispatcherMock->expects($this->once())
			->method('dispatchTyped')
			->with($this->isInstanceOf(VersionRestoredEvent::class));

		// Tiny retry budget/backoff so the test exercises the retry without waiting.
		$manager = new VersionManager($dispatcherMock, 1000, 1);

		$this->assertTrue($manager->rollback($versionMock));
		$this->assertSame(3, $attempts);
	}

	public function testRollbackGivesUpAfterRetryBudgetExhausted(): void {
		$versionMock = $this->createMock(IVersion::class);
		$backendMock = $this->createMock(IVersionBackend::class);
		$versionMock->method('getBackend')->willReturn($backendMock);

		// The live file stays locked for the whole retry budget.
		$backendMock->method('rollback')
			->with($versionMock)
			->willThrowException(new LockedException('files/foo.txt'));

		$dispatcherMock = $this->createMock(IEventDispatcher::class);
		$dispatcherMock->expects($this->never())->method('dispatchTyped');

		$manager = new VersionManager($dispatcherMock, 5, 1);

		$this->expectException(LockedException::class);
		$manager->rollback($versionMock);
	}
}
