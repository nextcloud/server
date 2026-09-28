<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\EncryptionWrapper;
use OC\Encryption\Manager;
use OC\Files\Storage\Wrapper\Encryption;
use OC\Memcache\ArrayCache;
use OCA\Files_Trashbin\Storage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IDisableEncryptionStorage;
use OCP\Files\Storage\IStorage;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class EncryptionWrapperTest extends TestCase {
	/** @var EncryptionWrapper */
	private $instance;

	/** @var \PHPUnit\Framework\MockObject\MockObject | LoggerInterface */
	private $logger;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OC\Encryption\Manager */
	private $manager;

	/** @var \PHPUnit\Framework\MockObject\MockObject|ArrayCache */
	private $arrayCache;

	protected function setUp(): void {
		parent::setUp();

		$this->arrayCache = $this->createMock(ArrayCache::class);
		$this->manager = $this->createMock(Manager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->instance = new EncryptionWrapper($this->arrayCache, $this->manager, $this->logger);
	}


	#[\PHPUnit\Framework\Attributes\DataProvider('provideWrapStorage')]
	public function testWrapStorage($expectedWrapped, $wrappedStorages): void {
		$storage = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()
			->getMock();

		foreach ($wrappedStorages as $wrapper) {
			$storage->expects($this->any())
				->method('instanceOfStorage')
				->willReturnMap([
					[$wrapper, true],
				]);
		}

		$mount = $this->getMockBuilder(IMountPoint::class)
			->disableOriginalConstructor()
			->getMock();

		$returnedStorage = $this->instance->wrapStorage('mountPoint', $storage, $mount);

		$this->assertEquals(
			$expectedWrapped,
			$returnedStorage->instanceOfStorage(Encryption::class),
			'Asserted that the storage is (not) wrapped with encryption'
		);
	}

	public static function provideWrapStorage(): array {
		return [
			// Wrap when not wrapped or not wrapped with storage
			[true, []],
			[true, [Storage::class]],

			// Do not wrap shared storages
			[false, [IDisableEncryptionStorage::class]],
		];
	}
}
