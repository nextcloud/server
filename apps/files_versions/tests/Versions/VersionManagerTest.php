<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\files_versions\tests\Versions;

use OC\Files\Storage\Local;
use OCA\Files_Versions\Versions\IVersionBackend;
use OCA\Files_Versions\Versions\VersionManager;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class VersionManagerTest extends TestCase {
	private function getBackend(bool $shouldUse = true): IVersionBackend {
		$backend = $this->createMock(IVersionBackend::class);
		$backend->method('useBackendForStorage')
			->willReturn($shouldUse);
		return $backend;
	}

	private function getStorage(string $class): IStorage {
		return $this->getMockBuilder($class)
			->disableOriginalConstructor()
			->setMethodsExcept(['instanceOfStorage'])
			->getMock();
	}

	public function testGetBackendSingle(): void {
		$manager = new VersionManager();
		$backend = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend);

		$this->assertEquals($backend, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendMoreSpecific(): void {
		$manager = new VersionManager();
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);

		$this->assertEquals($backend2, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendNoUse(): void {
		$manager = new VersionManager();
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend(false);
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);

		$this->assertEquals($backend1, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}

	public function testGetBackendMultiple(): void {
		$manager = new VersionManager();
		$backend1 = $this->getBackend();
		$backend2 = $this->getBackend(false);
		$backend3 = $this->getBackend();
		$manager->registerBackend(IStorage::class, $backend1);
		$manager->registerBackend(Local::class, $backend2);
		$manager->registerBackend(Local::class, $backend3);

		$this->assertEquals($backend3, $manager->getBackendForStorage($this->getStorage(Local::class)));
	}
}
