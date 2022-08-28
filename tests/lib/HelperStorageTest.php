<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Files\Storage\Temporary;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use Test\Traits\UserTrait;

/**
 * Test the storage functions of OC_Helper
 *
 * @group DB
 */
class HelperStorageTest extends \Test\TestCase {
	use UserTrait;

	/** @var string */
	private $user;
	/** @var \OC\Files\Storage\Storage */
	private $storageMock;
	/** @var \OC\Files\Storage\Storage */
	private $storage;
	private bool $savedQuotaIncludeExternalStorage;

	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->getUniqueID('user_');
		$this->createUser($this->user, $this->user);
		$this->savedQuotaIncludeExternalStorage = $this->getIncludeExternalStorage();

		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($this->user);
		\OC\Files\Filesystem::init($this->user, '/' . $this->user . '/files');

		/** @var IMountManager $manager */
		$manager = \OC::$server->get(IMountManager::class);
		$manager->removeMount('/' . $this->user);

		$this->storageMock = null;
	}

	protected function tearDown(): void {
		$this->setIncludeExternalStorage($this->savedQuotaIncludeExternalStorage);
		$this->user = null;

		if ($this->storageMock) {
			$this->storageMock->getCache()->clear();
			$this->storageMock = null;
		}
		\OC\Files\Filesystem::tearDown();

		\OC_User::setUserId('');
		\OC::$server->getConfig()->deleteAllUserValues($this->user);

		parent::tearDown();
	}

	/**
	 * Returns a storage mock that returns the given value as
	 * free space
	 *
	 * @param int $freeSpace free space value
	 * @return \OC\Files\Storage\Storage
	 */
	private function getStorageMock($freeSpace = 12) {
		$this->storageMock = $this->getMockBuilder(Temporary::class)
			->setMethods(['free_space'])
			->setConstructorArgs([''])
			->getMock();

		$this->storageMock->expects($this->once())
			->method('free_space')
			->willReturn(12);
		return $this->storageMock;
	}

	/**
	 * Test getting the storage info
	 */
	public function testGetStorageInfo() {
		$homeStorage = $this->getStorageMock(12);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');
		$homeStorage->file_put_contents('test.txt', '01234');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(17, $storageInfo['total']);
	}
	private function getIncludeExternalStorage(): bool {
		$class = new \ReflectionClass(\OC_Helper::class);
		$prop = $class->getProperty('quotaIncludeExternalStorage');
		$prop->setAccessible(true);
		return $prop->getValue(null) ?? false;
	}

	private function setIncludeExternalStorage(bool $include) {
		$class = new \ReflectionClass(\OC_Helper::class);
		$prop = $class->getProperty('quotaIncludeExternalStorage');
		$prop->setAccessible(true);
		$prop->setValue(null, $include);
	}

	/**
	 * Test getting the storage info, ignoring extra mount points
	 */
	public function testGetStorageInfoExcludingExtStorage() {
		$homeStorage = $this->getStorageMock(12);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');
		$homeStorage->file_put_contents('test.txt', '01234');

		$extStorage = new \OC\Files\Storage\Temporary([]);
		$extStorage->file_put_contents('extfile.txt', 'abcdefghijklmnopq');
		$extStorage->getScanner()->scan(''); // update root size

		$this->setIncludeExternalStorage(false);

		\OC\Files\Filesystem::mount($extStorage, [], '/' . $this->user . '/files/ext');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(17, $storageInfo['total']);
	}

	/**
	 * Test getting the storage info, including extra mount points
	 */
	public function testGetStorageInfoIncludingExtStorage() {
		$homeStorage = new \OC\Files\Storage\Temporary([]);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');
		$homeStorage->file_put_contents('test.txt', '01234');

		$extStorage = new \OC\Files\Storage\Temporary([]);
		$extStorage->file_put_contents('extfile.txt', 'abcdefghijklmnopq');
		$extStorage->getScanner()->scan(''); // update root size

		\OC\Files\Filesystem::mount($extStorage, [], '/' . $this->user . '/files/ext');

		$this->setIncludeExternalStorage(true);

		$config = \OC::$server->get(IConfig::class);
		$config->setUserValue($this->user, 'files', 'quota', '25');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(3, $storageInfo['free']);
		$this->assertEquals(22, $storageInfo['used']);
		$this->assertEquals(25, $storageInfo['total']);

		$config->setUserValue($this->user, 'files', 'quota', 'default');
	}

	/**
	 * Test getting the storage info excluding extra mount points
	 * when user has no quota set, even when quota ext storage option
	 * was set
	 */
	public function testGetStorageInfoIncludingExtStorageWithNoUserQuota() {
		$homeStorage = $this->getStorageMock(12);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');
		$homeStorage->file_put_contents('test.txt', '01234');

		$extStorage = new \OC\Files\Storage\Temporary([]);
		$extStorage->file_put_contents('extfile.txt', 'abcdefghijklmnopq');
		$extStorage->getScanner()->scan(''); // update root size

		\OC\Files\Filesystem::mount($extStorage, [], '/' . $this->user . '/files/ext');

		$config = \OC::$server->getConfig();
		$this->setIncludeExternalStorage(true);

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free'], '12 bytes free in home storage');
		$this->assertEquals(22, $storageInfo['used'], '5 bytes of home storage and 17 bytes of the temporary storage are used');
		$this->assertEquals(34, $storageInfo['total'], '5 bytes used and 12 bytes free in home storage as well as 17 bytes used in temporary storage');
	}


	/**
	 * Test getting the storage info with quota enabled
	 */
	public function testGetStorageInfoWithQuota() {
		$homeStorage = $this->getStorageMock(12);
		$homeStorage->file_put_contents('test.txt', '01234');
		$homeStorage = new \OC\Files\Storage\Wrapper\Quota(
			[
				'storage' => $homeStorage,
				'quota' => 7
			]
		);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(2, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		$this->assertEquals(7, $storageInfo['total']);
	}

	/**
	 * Test getting the storage info when data exceeds quota
	 */
	public function testGetStorageInfoWhenSizeExceedsQuota() {
		$homeStorage = $this->getStorageMock(12);
		$homeStorage->file_put_contents('test.txt', '0123456789');
		$homeStorage = new \OC\Files\Storage\Wrapper\Quota(
			[
				'storage' => $homeStorage,
				'quota' => 7
			]
		);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');

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
	public function testGetStorageInfoWhenFreeSpaceLessThanQuota() {
		$homeStorage = $this->getStorageMock(12);
		$homeStorage->file_put_contents('test.txt', '01234');
		$homeStorage = new \OC\Files\Storage\Wrapper\Quota(
			[
				'storage' => $homeStorage,
				'quota' => 18
			]
		);
		\OC\Files\Filesystem::mount($homeStorage, [], '/' . $this->user . '/files');

		$storageInfo = \OC_Helper::getStorageInfo('');
		$this->assertEquals(12, $storageInfo['free']);
		$this->assertEquals(5, $storageInfo['used']);
		// total = free + used (because quota > total)
		$this->assertEquals(17, $storageInfo['total']);
	}
}
