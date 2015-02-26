<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use OCA\Files_Trashbin;

/**
 * Class Test_Encryption_Crypt
 */
class Test_Trashbin extends \Test\TestCase {

	const TEST_TRASHBIN_USER1 = "test-trashbin-user1";
	const TEST_TRASHBIN_USER2 = "test-trashbin-user2";

	private $trashRoot1;
	private $trashRoot2;

	private static $encryptionStatus;
	private static $rememberRetentionObligation;
	private static $rememberAutoExpire;

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

		//disable encryption
		self::$encryptionStatus = \OC_App::isEnabled('files_encryption');
		\OC_App::disable('files_encryption');

		//configure trashbin
		self::$rememberRetentionObligation = \OC_Config::getValue('trashbin_retention_obligation', Files_Trashbin\Trashbin::DEFAULT_RETENTION_OBLIGATION);
		\OC_Config::setValue('trashbin_retention_obligation', 2);
		self::$rememberAutoExpire = \OC_Config::getValue('trashbin_auto_expire', true);
		\OC_Config::setValue('trashbin_auto_expire', true);


		// register hooks
		Files_Trashbin\Trashbin::registerHooks();

		// create test user
		self::loginHelper(self::TEST_TRASHBIN_USER2, true);
		self::loginHelper(self::TEST_TRASHBIN_USER1, true);
	}



	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_TRASHBIN_USER1);

		if (self::$encryptionStatus === true) {
			\OC_App::enable('files_encryption');
		}

		\OC_Config::setValue('trashbin_retention_obligation', self::$rememberRetentionObligation);
		\OC_Config::setValue('trashbin_auto_expire', self::$rememberAutoExpire);

		\OC_Hook::clear();

		\OC\Files\Filesystem::getLoader()->removeStorageWrapper('oc_trashbin');

		parent::tearDownAfterClass();
	}

	protected function setUp() {
		parent::setUp();

		$this->trashRoot1 = '/' . self::TEST_TRASHBIN_USER1 . '/files_trashbin';
		$this->trashRoot2 = '/' . self::TEST_TRASHBIN_USER2 . '/files_trashbin';
		$this->rootView = new \OC\Files\View();
		self::loginHelper(self::TEST_TRASHBIN_USER1);
	}

	protected function tearDown() {
		$this->rootView->deleteAll($this->trashRoot1);
		$this->rootView->deleteAll($this->trashRoot2);

		parent::tearDown();
	}

	/**
	 * test expiration of files older then the max storage time defined for the trash
	 */
	public function testExpireOldFiles() {

		$currentTime = time();
		$expireAt = $currentTime - 2*24*60*60;
		$expiredDate = $currentTime - 3*24*60*60;

		// create some files
		\OC\Files\Filesystem::file_put_contents('file1.txt', 'file1');
		\OC\Files\Filesystem::file_put_contents('file2.txt', 'file2');
		\OC\Files\Filesystem::file_put_contents('file3.txt', 'file3');

		// delete them so that they end up in the trash bin
		\OC\Files\Filesystem::unlink('file1.txt');
		\OC\Files\Filesystem::unlink('file2.txt');
		\OC\Files\Filesystem::unlink('file3.txt');

		//make sure that files are in the trash bin
		$filesInTrash = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'name');
		$this->assertSame(3, count($filesInTrash));

		// every second file will get a date in the past so that it will get expired
		$manipulatedList = $this->manipulateDeleteTime($filesInTrash, $this->trashRoot1, $expiredDate);

		$testClass = new TrashbinForTesting();
		list($sizeOfDeletedFiles, $count) = $testClass->dummyDeleteExpiredFiles($manipulatedList, $expireAt);

		$this->assertSame(10, $sizeOfDeletedFiles);
		$this->assertSame(2, $count);

		// only file2.txt should be left
		$remainingFiles = array_slice($manipulatedList, $count);
		$this->assertSame(1, count($remainingFiles));
		$remainingFile = reset($remainingFiles);
		$this->assertSame('file2.txt', $remainingFile['name']);

		// check that file1.txt and file3.txt was really deleted
		$newTrashContent = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);
		$this->assertSame(1, count($newTrashContent));
		$element = reset($newTrashContent);
		$this->assertSame('file2.txt', $element['name']);
	}

	/**
	 * test expiration of files older then the max storage time defined for the trash
	 * in this test we delete a shared file and check if both trash bins, the one from
	 * the owner of the file and the one from the user who deleted the file get expired
	 * correctly
	 */
	public function testExpireOldFilesShared() {

		$currentTime = time();
		$folder = "trashTest-" . $currentTime . '/';
		$expiredDate = $currentTime - 3*24*60*60;

		// create some files
		\OC\Files\Filesystem::mkdir($folder);
		\OC\Files\Filesystem::file_put_contents($folder . 'user1-1.txt', 'file1');
		\OC\Files\Filesystem::file_put_contents($folder . 'user1-2.txt', 'file2');
		\OC\Files\Filesystem::file_put_contents($folder . 'user1-3.txt', 'file3');
		\OC\Files\Filesystem::file_put_contents($folder . 'user1-4.txt', 'file4');

		//share user1-4.txt with user2
		$fileInfo = \OC\Files\Filesystem::getFileInfo($folder);
		$result = \OCP\Share::shareItem('folder', $fileInfo->getId(), \OCP\Share::SHARE_TYPE_USER, self::TEST_TRASHBIN_USER2, 31);
		$this->assertTrue($result);

		// delete them so that they end up in the trash bin
		\OC\Files\Filesystem::unlink($folder . 'user1-1.txt');
		\OC\Files\Filesystem::unlink($folder . 'user1-2.txt');
		\OC\Files\Filesystem::unlink($folder . 'user1-3.txt');

		$filesInTrash = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'name');
		$this->assertSame(3, count($filesInTrash));

		// every second file will get a date in the past so that it will get expired
		$this->manipulateDeleteTime($filesInTrash, $this->trashRoot1, $expiredDate);

		// login as user2
		self::loginHelper(self::TEST_TRASHBIN_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists($folder . "user1-4.txt"));

		// create some files
		\OC\Files\Filesystem::file_put_contents('user2-1.txt', 'file1');
		\OC\Files\Filesystem::file_put_contents('user2-2.txt', 'file2');

		// delete them so that they end up in the trash bin
		\OC\Files\Filesystem::unlink('user2-1.txt');
		\OC\Files\Filesystem::unlink('user2-2.txt');

		$filesInTrashUser2 = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER2, 'name');
		$this->assertSame(2, count($filesInTrashUser2));

		// every second file will get a date in the past so that it will get expired
		$this->manipulateDeleteTime($filesInTrashUser2, $this->trashRoot2, $expiredDate);

		\OC\Files\Filesystem::unlink($folder . 'user1-4.txt');

		$filesInTrashUser2AfterDelete = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER2);

		// user2-1.txt should have been expired
		$this->verifyArray($filesInTrashUser2AfterDelete, array('user2-2.txt', 'user1-4.txt'));

		// user1-1.txt and user1-3.txt should have been expired
		$filesInTrashUser1AfterDelete = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);

		$this->verifyArray($filesInTrashUser1AfterDelete, array('user1-2.txt', 'user1-4.txt'));
	}

	/**
	 * verify that the array contains the expected results
	 * @param array $result
	 * @param array $expected
	 */
	private function verifyArray($result, $expected) {
		$this->assertSame(count($expected), count($result));
		foreach ($expected as $expectedFile) {
			$found = false;
			foreach ($result as $fileInTrash) {
				if ($expectedFile === $fileInTrash['name']) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				// if we didn't found the expected file, something went wrong
				$this->assertTrue(false, "can't find expected file '" . $expectedFile .  "' in trash bin");
			}
		}
	}

	private function manipulateDeleteTime($files, $trashRoot, $expireDate) {
		$counter = 0;
		foreach ($files as &$file) {
			// modify every second file
			$counter = ($counter + 1) % 2;
			if ($counter === 1) {
				$source = $trashRoot . '/files/' . $file['name'].'.d'.$file['mtime'];
				$target = \OC\Files\Filesystem::normalizePath($trashRoot . '/files/' . $file['name'] . '.d' . $expireDate);
				$this->rootView->rename($source, $target);
				$file['mtime'] = $expireDate;
			}
		}
		return \OCA\Files\Helper::sortFiles($files, 'mtime');
	}


	/**
	 * test expiration of old files in the trash bin until the max size
	 * of the trash bin is met again
	 */
	public function testExpireOldFilesUtilLimitsAreMet() {

		// create some files
		\OC\Files\Filesystem::file_put_contents('file1.txt', 'file1');
		\OC\Files\Filesystem::file_put_contents('file2.txt', 'file2');
		\OC\Files\Filesystem::file_put_contents('file3.txt', 'file3');

		// delete them so that they end up in the trash bin
		\OC\Files\Filesystem::unlink('file3.txt');
		sleep(1); // make sure that every file has a unique mtime
		\OC\Files\Filesystem::unlink('file2.txt');
		sleep(1); // make sure that every file has a unique mtime
		\OC\Files\Filesystem::unlink('file1.txt');

		//make sure that files are in the trash bin
		$filesInTrash = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1, 'mtime');
		$this->assertSame(3, count($filesInTrash));

		$testClass = new TrashbinForTesting();
		$sizeOfDeletedFiles = $testClass->dummyDeleteFiles($filesInTrash, -8);

		// the two oldest files (file3.txt and file2.txt) should be deleted
		$this->assertSame(10, $sizeOfDeletedFiles);

		$newTrashContent = OCA\Files_Trashbin\Helper::getTrashFiles('/', self::TEST_TRASHBIN_USER1);
		$this->assertSame(1, count($newTrashContent));
		$element = reset($newTrashContent);
		$this->assertSame('file1.txt', $element['name']);
	}

	/**
	 * @param string $user
	 * @param bool $create
	 * @param bool $password
	 */
	public static function loginHelper($user, $create = false) {
		if ($create) {
			try {
				\OC_User::createUser($user, $user);
			} catch(\Exception $e) { // catch username is already being used from previous aborted runs

			}
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
	}
}


// just a dummy class to make protected methods available for testing
class TrashbinForTesting extends Files_Trashbin\Trashbin {
	public function dummyDeleteExpiredFiles($files, $limit) {
		// dummy value for $retention_obligation because it is not needed here
		return parent::deleteExpiredFiles($files, \Test_Trashbin::TEST_TRASHBIN_USER1, $limit, 0);
	}

	public function dummyDeleteFiles($files, $availableSpace) {
		return parent::deleteFiles($files, \Test_Trashbin::TEST_TRASHBIN_USER1, $availableSpace);
	}
}
