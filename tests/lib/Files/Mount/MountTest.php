<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Mount;

use OC\Files\Storage\StorageFactory;
use OC\Files\Storage\Wrapper\Wrapper;

class MountTest extends \Test\TestCase {
	public function testFromStorageObject() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->disableOriginalConstructor()
			->getMock();
		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo');
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $mount->getStorage());
	}

	public function testFromStorageClassname() {
		$mount = new \OC\Files\Mount\MountPoint('\OC\Files\Storage\Temporary', '/foo');
		$this->assertInstanceOf('\OC\Files\Storage\Temporary', $mount->getStorage());
	}

	public function testWrapper() {
		$test = $this;
		$wrapper = function ($mountPoint, $storage) use (&$test) {
			$test->assertEquals('/foo/', $mountPoint);
			$test->assertInstanceOf('\OC\Files\Storage\Storage', $storage);
			return new Wrapper(['storage' => $storage]);
		};

		$loader = new StorageFactory();
		$loader->addStorageWrapper('test_wrapper', $wrapper);

		$storage = $this->getMockBuilder('\OC\Files\Storage\Temporary')
			->disableOriginalConstructor()
			->getMock();
		$mount = new \OC\Files\Mount\MountPoint($storage, '/foo', [], $loader);
		$this->assertInstanceOf('\OC\Files\Storage\Wrapper\Wrapper', $mount->getStorage());
	}
}
