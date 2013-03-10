<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files;

use \OC\Files\Storage\Temporary;

class LongId extends Temporary {
	public function getId() {
		return 'long:' . str_repeat('foo', 50) . parent::getId();
	}
}

class Mount extends \PHPUnit_Framework_TestCase {
	public function setup() {
		\OC_Util::setupFS();
		\OC\Files\Mount::clear();
	}

	public function testFind() {
		$this->assertNull(\OC\Files\Mount::find('/'));

		$rootMount = new \OC\Files\Mount(new Temporary(array()), '/');
		$this->assertEquals($rootMount, \OC\Files\Mount::find('/'));
		$this->assertEquals($rootMount, \OC\Files\Mount::find('/foo/bar'));

		$storage = new Temporary(array());
		$mount = new \OC\Files\Mount($storage, '/foo');
		$this->assertEquals($rootMount, \OC\Files\Mount::find('/'));
		$this->assertEquals($mount, \OC\Files\Mount::find('/foo/bar'));

		$this->assertEquals(1, count(\OC\Files\Mount::findIn('/')));
		new \OC\Files\Mount(new Temporary(array()), '/bar');
		$this->assertEquals(2, count(\OC\Files\Mount::findIn('/')));

		$id = $mount->getStorageId();
		$this->assertEquals(array($mount), \OC\Files\Mount::findByStorageId($id));

		$mount2 = new \OC\Files\Mount($storage, '/foo/bar');
		$this->assertEquals(array($mount, $mount2), \OC\Files\Mount::findByStorageId($id));
	}

	public function testLong() {
		$storage = new LongId(array());
		$mount = new \OC\Files\Mount($storage, '/foo');

		$id = $mount->getStorageId();
		$storageId = $storage->getId();
		$this->assertEquals(array($mount), \OC\Files\Mount::findByStorageId($id));
		$this->assertEquals(array($mount), \OC\Files\Mount::findByStorageId($storageId));
		$this->assertEquals(array($mount), \OC\Files\Mount::findByStorageId(md5($storageId)));
	}
}
