<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Test the storage functions of OC_Helper
 */
class Test_Helper_Storage extends PHPUnit_Framework_TestCase {
	private $user;
	private $storageMock;

	public function setUp() {
		$this->user = 'user_' . uniqid();
		\OC\Files\Filesystem::tearDown();
		\OC\Files\Filesystem::init($this->user, '/' . $this->user . '/files');

		$this->storageMock = $this->getMock(
			'\OC\Files\Storage\Temporary',
			array('free_space'),
			array('')
		);

		\OC\Files\Filesystem::clearMounts();

		$this->storageMock->expects($this->once())
			->method('free_space')
			->will($this->returnValue(12));
	}

	public function tearDown() {
		$this->user = null;

		$this->storageMock->getCache()->clear();
		\OC\Files\Filesystem::tearDown();
	}

	/**
	 * Test getting the storage info
	 */
	function testGetStorageInfo() {
		\OC\Files\Filesystem::mount($this->storageMock, array(), '/' . $this->user . '/files');
		$this->storageMock->file_put_contents('test.txt', '01234');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(17, $storageInfo['total']);
	}

	/**
	 * Test getting the storage info with quota enabled
	 */
	function testGetStorageInfoWithQuota() {
		$this->storageMock->file_put_contents('test.txt', '01234');
		$this->storageMock = new \OC\Files\Storage\Wrapper\Quota(
			array(
				'storage' => $this->storageMock,
				'quota' => 7
			)
		);
		\OC\Files\Filesystem::mount($this->storageMock, array(), '/' . $this->user . '/files');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(2, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(7, $storageInfo['total']);
	}

	/**
	 * Test getting the storage info when data exceeds quota
	 */
	function testGetStorageInfoWhenSizeExceedsQuota() {
		$this->storageMock->file_put_contents('test.txt', '0123456789');
		$this->storageMock = new \OC\Files\Storage\Wrapper\Quota(
			array(
				'storage' => $this->storageMock,
				'quota' => 7
			)
		);
		\OC\Files\Filesystem::mount($this->storageMock, array(), '/' . $this->user . '/files');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(0, $storageInfo['free']);
		$this->assertEquals(10, $storageInfo['used']);
		// total = quota
		$this->assertEquals(7, $storageInfo['total']);
	}

	/**
	 * Test getting the storage info when the remaining
	 * free storage space is less than the quota
	 */
	function testGetStorageInfoWhenFreeSpaceLessThanQuota() {
		$this->storageMock->file_put_contents('test.txt', '01234');
		$this->storageMock = new \OC\Files\Storage\Wrapper\Quota(
			array(
				'storage' => $this->storageMock,
				'quota' => 18
			)
		);
		\OC\Files\Filesystem::mount($this->storageMock, array(), '/' . $this->user . '/files');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		// total = free + used (because quota > total)
		$this->assertEquals(17, $storageInfo['total']);
	}
}
