<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\EncryptionWrapper;
use OC\Files\Storage\Wrapper\Encryption;
use OCA\Files_Trashbin\Storage;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IDisableEncryptionStorage;
use Test\TestCase;

class EncryptionWrapperTest extends TestCase {
	/** @var EncryptionWrapper */
	private $instance;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->instance = $this->createInstanceWithMocks(EncryptionWrapper::class);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('provideWrapStorage')]
	public function testWrapStorage($expectedWrapped, $wrappedStorages): void {
		$storage = $this->getMockBuilder(Storage::class)
			->disableOriginalConstructor()
			->getMock();

		$storage->expects($this->any())
			->method('instanceOfStorage')
			->willReturnCallback(fn (string $storage): bool => in_array($storage, $wrappedStorages, true));

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
