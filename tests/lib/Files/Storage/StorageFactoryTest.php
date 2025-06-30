<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Mount\MountPoint;
use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use Test\TestCase;

class DummyWrapper extends Wrapper {
	public $data;

	public function __construct(array $parameters) {
		parent::__construct($parameters);
		if (isset($parameters['data'])) {
			$this->data = $parameters['data'];
		}
	}
}

class StorageFactoryTest extends TestCase {
	public function testSimpleWrapper(): void {
		$instance = new StorageFactory();
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/foo', [[]], $instance);
		$instance->addStorageWrapper('dummy', function ($mountPoint, IStorage $storage, IMountPoint $mount) {
			$this->assertInstanceOf('\OC\Files\Storage\Temporary', $storage);
			$this->assertEquals('/foo/', $mount->getMountPoint());
			$this->assertEquals('/foo/', $mountPoint);
			return new DummyWrapper(['storage' => $storage]);
		});
		$wrapped = $mount->getStorage();
		$this->assertInstanceOf('\Test\Files\Storage\DummyWrapper', $wrapped);
	}

	public function testRemoveWrapper(): void {
		$instance = new StorageFactory();
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/foo', [[]], $instance);
		$instance->addStorageWrapper('dummy', function ($mountPoint, IStorage $storage) {
			return new DummyWrapper(['storage' => $storage]);
		});
		$instance->removeStorageWrapper('dummy');
		$wrapped = $mount->getStorage();
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $wrapped);
	}

	public function testWrapperPriority(): void {
		$instance = new StorageFactory();
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/foo', [[]], $instance);
		$instance->addStorageWrapper('dummy1', function ($mountPoint, IStorage $storage) {
			return new DummyWrapper(['storage' => $storage, 'data' => 1]);
		}, 1);
		$instance->addStorageWrapper('dummy2', function ($mountPoint, IStorage $storage) {
			return new DummyWrapper(['storage' => $storage, 'data' => 100]);
		}, 100);
		$instance->addStorageWrapper('dummy3', function ($mountPoint, IStorage $storage) {
			return new DummyWrapper(['storage' => $storage, 'data' => 50]);
		}, 50);
		/** @var \Test\Files\Storage\DummyWrapper $wrapped */
		$wrapped = $mount->getStorage();
		$this->assertInstanceOf('\Test\Files\Storage\DummyWrapper', $wrapped);
		$this->assertEquals(1, $wrapped->data);// lowest priority is applied last, called first
		$this->assertEquals(50, $wrapped->getWrapperStorage()->data);
		$this->assertEquals(100, $wrapped->getWrapperStorage()->getWrapperStorage()->data);
	}
}
