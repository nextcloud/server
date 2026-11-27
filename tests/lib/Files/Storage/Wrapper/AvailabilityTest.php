<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\Storage\Wrapper;

use OC\Files\Cache\Storage as StorageCache;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Availability;
use OCP\Files\StorageNotAvailableException;

class AvailabilityTest extends \Test\TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject|StorageCache */
	protected $storageCache;
	/** @var \PHPUnit\Framework\MockObject\MockObject|Temporary */
	protected $storage;
	/** @var Availability */
	protected $wrapper;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->storageCache = $this->createMock(StorageCache::class);

		$this->storage = $this->createMock(Temporary::class);
		$this->storage->expects($this->any())
			->method('getStorageCache')
			->willReturn($this->storageCache);

		$this->wrapper = new Availability(['storage' => $this->storage]);
	}

	/**
	 * Storage is available
	 */
	public function testAvailableStorageForwardsOperationWithoutRecheck(): void {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir');

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked unavailable, TTL not expired
	 */
	public function testUnavailableStorageRejectsOperationBeforeRetryInterval(): void {
		$this->expectException(StorageNotAvailableException::class);

		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => time()]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->never())
			->method('mkdir');

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked unavailable, TTL expired
	 */
	public function testSuccessfulRecheckIsRemembered(): void {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => 0]);
		$this->storage->expects($this->once())
			->method('test')
			->willReturn(true);
		$calls = [
			false, // prevents concurrent rechecks
			true, // sets correct availability
		];
		$this->storage->expects($this->exactly(2))
			->method('setAvailability')
			->willReturnCallback(function ($value) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, $value);
			});
		$this->storage->expects($this->exactly(2))
			->method('mkdir')
			->willReturn(true);

		$this->assertTrue($this->wrapper->mkdir('foobar'));
		$this->assertTrue($this->wrapper->mkdir('another'));
	}

	public static function failedRecheckProvider(): array {
		return [
			'returns false' => [false],
			'throws exception' => [true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('failedRecheckProvider')]
	public function testFailedRecheckIsRemembered(bool $throws): void {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => 0]);

		$probe = $this->storage->expects($this->once())
			->method('test');
		if ($throws) {
			$probe->willThrowException(new StorageNotAvailableException());
		} else {
			$probe->willReturn(false);
		}

		$this->storage->expects($this->exactly(2))
			->method('setAvailability')
			->with(false);
		$this->storage->expects($this->never())
			->method('mkdir');

		foreach (['first', 'second'] as $path) {
			try {
				$this->wrapper->mkdir($path);
				$this->fail('Unavailable storage must reject the operation');
			} catch (StorageNotAvailableException) {
			}
		}
	}

	public function testStorageNotAvailableExceptionMarksStorageUnavailable(): void {
		$this->expectException(StorageNotAvailableException::class);

		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir')
			->willThrowException(new StorageNotAvailableException());
		$this->storageCache->expects($this->once())
			->method('setAvailability')
			->with($this->equalTo(false));

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage available, but call fails
	 * Method failure does not indicate storage unavailability
	 */
	public function testOperationReturningFalseDoesNotMarkStorageUnavailable(): void {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir')
			->willReturn(false);
		$this->storage->expects($this->never())
			->method('setAvailability');

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage available, but throws exception
	 * Standard exception does not indicate storage unavailability
	 */
	public function testOtherExceptionDoesNotMarkStorageUnavailable(): void {
		$this->expectException(\Exception::class);

		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir')
			->willThrowException(new \Exception());
		$this->storage->expects($this->never())
			->method('setAvailability');

		$this->wrapper->mkdir('foobar');
	}

	public function testUnavailableStorageBlocksSubsequentOperations(): void {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage
			->expects($this->once()) // load-bearing `once`
			->method('mkdir')
			->willThrowException(new StorageNotAvailableException());

		try {
			$this->wrapper->mkdir('foobar');
			$this->fail();
		} catch (StorageNotAvailableException) {
		}

		try {
			$this->wrapper->mkdir('foobar');
			$this->fail();
		} catch (StorageNotAvailableException) {
		}
	}
}
