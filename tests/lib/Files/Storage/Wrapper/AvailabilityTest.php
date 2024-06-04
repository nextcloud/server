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
	/** @var Availability  */
	protected $wrapper;

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
	public function testAvailable() {
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
	 *
	 */
	public function testUnavailable() {
		$this->expectException(\OCP\Files\StorageNotAvailableException::class);

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
	public function testUnavailableRecheck() {
		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => false, 'last_checked' => 0]);
		$this->storage->expects($this->once())
			->method('test')
			->willReturn(true);
		$this->storage->expects($this->exactly(2))
			->method('setAvailability')
			->withConsecutive(
				[$this->equalTo(false)], // prevents concurrent rechecks
				[$this->equalTo(true)] // sets correct availability
			);
		$this->storage->expects($this->once())
			->method('mkdir');

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage marked available, but throws StorageNotAvailableException
	 *
	 */
	public function testAvailableThrowStorageNotAvailable() {
		$this->expectException(\OCP\Files\StorageNotAvailableException::class);

		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir')
			->will($this->throwException(new StorageNotAvailableException()));
		$this->storageCache->expects($this->once())
			->method('setAvailability')
			->with($this->equalTo(false));

		$this->wrapper->mkdir('foobar');
	}

	/**
	 * Storage available, but call fails
	 * Method failure does not indicate storage unavailability
	 */
	public function testAvailableFailure() {
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
	 *
	 */
	public function testAvailableThrow() {
		$this->expectException(\Exception::class);

		$this->storage->expects($this->once())
			->method('getAvailability')
			->willReturn(['available' => true, 'last_checked' => 0]);
		$this->storage->expects($this->never())
			->method('test');
		$this->storage->expects($this->once())
			->method('mkdir')
			->will($this->throwException(new \Exception()));
		$this->storage->expects($this->never())
			->method('setAvailability');

		$this->wrapper->mkdir('foobar');
	}
}
