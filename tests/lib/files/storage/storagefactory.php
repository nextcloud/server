<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

use OC\Files\Mount\MountPoint;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage;
use Test\TestCase;
use OC\Files\Storage\Wrapper\Wrapper;

class DummyWrapper extends Wrapper {

}

class StorageFactory extends TestCase {
	public function testSimpleWrapper() {
		$instance = new \OC\Files\Storage\StorageFactory();
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/foo', [[]], $instance);
		$instance->addStorageWrapper('dummy', function ($mountPoint, Storage $storage, IMountPoint $mount) {
			$this->assertInstanceOf('\OC\Files\Storage\Temporary', $storage);
			$this->assertEquals('/foo/', $mount->getMountPoint());
			$this->assertEquals('/foo/', $mountPoint);
			return new DummyWrapper(['storage' => $storage]);
		});
		$wrapped = $mount->getStorage();
		$this->assertInstanceOf('\Test\Files\Storage\DummyWrapper', $wrapped);
	}

	public function testRemoveWrapper() {
		$instance = new \OC\Files\Storage\StorageFactory();
		$mount = new MountPoint('\OC\Files\Storage\Temporary', '/foo', [[]], $instance);
		$instance->addStorageWrapper('dummy', function ($mountPoint, Storage $storage) {
			return new DummyWrapper(['storage' => $storage]);
		});
		$instance->removeStorageWrapper('dummy');
		$wrapped = $mount->getStorage();
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $wrapped);
	}
}
