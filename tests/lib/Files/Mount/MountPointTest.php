<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Mount;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\StorageFactory;
use OC\Lockdown\Filesystem\NullStorage;
use OCP\Files\Storage\IStorage;

class MountPointTest extends \Test\TestCase {
	public function testGetStorage(): void {
		$storage = $this->createMock(IStorage::class);
		$storage->expects($this->once())
			->method('getId')
			->willReturn(123);

		$loader = $this->createMock(StorageFactory::class);
		$loader->expects($this->once())
			->method('wrap')
			->willReturn($storage);

		$mountPoint = new MountPoint(
			// just use this because a real class is needed
			NullStorage::class,
			'/mountpoint',
			null,
			$loader
		);

		$this->assertEquals($storage, $mountPoint->getStorage());
		$this->assertEquals(123, $mountPoint->getStorageId());
		$this->assertEquals('/mountpoint/', $mountPoint->getMountPoint());

		$mountPoint->setMountPoint('another');
		$this->assertEquals('/another/', $mountPoint->getMountPoint());
	}

	public function testInvalidStorage(): void {
		$loader = $this->createMock(StorageFactory::class);
		$loader->expects($this->once())
			->method('wrap')
			->willThrowException(new \Exception('Test storage init exception'));

		$called = false;
		$wrapper = function ($mountPoint, $storage) use ($called): void {
			$called = true;
		};

		$mountPoint = new MountPoint(
			// just use this because a real class is needed
			NullStorage::class,
			'/mountpoint',
			null,
			$loader
		);

		$this->assertNull($mountPoint->getStorage());
		// call it again to make sure the init code only ran once
		$this->assertNull($mountPoint->getStorage());

		$this->assertNull($mountPoint->getStorageId());

		// wrapping doesn't fail
		$mountPoint->wrapStorage($wrapper);

		$this->assertNull($mountPoint->getStorage());

		// storage wrapper never called
		$this->assertFalse($called);
	}
}
