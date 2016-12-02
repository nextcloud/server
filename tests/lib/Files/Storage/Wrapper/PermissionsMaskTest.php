<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage\Wrapper;

use OCP\Constants;

class PermissionsMaskTest extends \Test\Files\Storage\Storage {

	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	public function setUp() {
		parent::setUp();
		$this->sourceStorage = new \OC\Files\Storage\Temporary(array());
		$this->instance = $this->getMaskedStorage(Constants::PERMISSION_ALL);
	}

	public function tearDown() {
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	protected function getMaskedStorage($mask) {
		return new \OC\Files\Storage\Wrapper\PermissionsMask(array(
			'storage' => $this->sourceStorage,
			'mask' => $mask
		));
	}

	public function testMkdirNoCreate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE);
		$this->assertFalse($storage->mkdir('foo'));
		$this->assertFalse($storage->file_exists('foo'));
	}

	public function testRmdirNoDelete() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
		$this->assertTrue($storage->mkdir('foo'));
		$this->assertTrue($storage->file_exists('foo'));
		$this->assertFalse($storage->rmdir('foo'));
		$this->assertTrue($storage->file_exists('foo'));
	}

	public function testTouchNewFileNoCreate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE);
		$this->assertFalse($storage->touch('foo'));
		$this->assertFalse($storage->file_exists('foo'));
	}

	public function testTouchNewFileNoUpdate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE);
		$this->assertTrue($storage->touch('foo'));
		$this->assertTrue($storage->file_exists('foo'));
	}

	public function testTouchExistingFileNoUpdate() {
		$this->sourceStorage->touch('foo');
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE);
		$this->assertFalse($storage->touch('foo'));
	}

	public function testUnlinkNoDelete() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_DELETE);
		$this->assertTrue($storage->touch('foo'));
		$this->assertTrue($storage->file_exists('foo'));
		$this->assertFalse($storage->unlink('foo'));
		$this->assertTrue($storage->file_exists('foo'));
	}

	public function testPutContentsNewFileNoUpdate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE);
		$this->assertEquals(3, $storage->file_put_contents('foo', 'bar'));
		$this->assertEquals('bar', $storage->file_get_contents('foo'));
	}

	public function testPutContentsNewFileNoCreate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE);
		$this->assertFalse($storage->file_put_contents('foo', 'bar'));
	}

	public function testPutContentsExistingFileNoUpdate() {
		$this->sourceStorage->touch('foo');
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE);
		$this->assertFalse($storage->file_put_contents('foo', 'bar'));
	}

	public function testFopenExistingFileNoUpdate() {
		$this->sourceStorage->touch('foo');
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_UPDATE);
		$this->assertFalse($storage->fopen('foo', 'w'));
	}

	public function testFopenNewFileNoCreate() {
		$storage = $this->getMaskedStorage(Constants::PERMISSION_ALL - Constants::PERMISSION_CREATE);
		$this->assertFalse($storage->fopen('foo', 'w'));
	}
}
