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
}
