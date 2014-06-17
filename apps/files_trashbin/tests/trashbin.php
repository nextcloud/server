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

require_once __DIR__ . '/../../../lib/base.php';

use OCA\Files_Trashbin;

/**
 * Class Test_Encryption_Crypt
 */
class Test_Trashbin extends \PHPUnit_Framework_TestCase {

	const TEST_TRASHBIN_USER1 = "test-trashbin-user1";

	private $trashRoot;

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// register hooks
		Files_Trashbin\Trashbin::registerHooks();

		// create test user
		self::loginHelper(self::TEST_TRASHBIN_USER1, true);
	}



	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_TRASHBIN_USER1);

		\OC_Hook::clear();
	}

	public function setUp() {
		$this->trashRoot = '/' . self::TEST_TRASHBIN_USER1 . '/files_trashbin';
		$this->rootView = new \OC\Files\View();
	}

	public function tearDown() {
		$this->rootView->deleteAll($this->trashRoot);
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
		$filesInTrash = OCA\Files_Trashbin\Helper::getTrashFiles('/');
		$this->assertSame(3, count($filesInTrash));

		$manipulatedList = $this->manipulateDeleteTime($filesInTrash, $expiredDate);

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
		$newTrashContent = OCA\Files_Trashbin\Helper::getTrashFiles('/');
		$this->assertSame(1, count($newTrashContent));
		$element = reset($newTrashContent);
		$this->assertSame('file2.txt', $element['name']);
	}

	private function manipulateDeleteTime($files, $expireDate) {
		$counter = 0;
		foreach ($files as &$file) {
			// modify every second file
			$counter = ($counter + 1) % 2;
			if ($counter === 1) {
				$source = $this->trashRoot . '/files/' . $file['name'].'.d'.$file['mtime'];
				$target = \OC\Files\Filesystem::normalizePath($this->trashRoot . '/files/' . $file['name'] . '.d' . $expireDate);
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
		$filesInTrash = OCA\Files_Trashbin\Helper::getTrashFiles('/', 'mtime');
		$this->assertSame(3, count($filesInTrash));

		$testClass = new TrashbinForTesting();
		$sizeOfDeletedFiles = $testClass->dummyDeleteFiles($filesInTrash, -8);

		// the two oldest files (file3.txt and file2.txt) should be deleted
		$this->assertSame(10, $sizeOfDeletedFiles);

		$newTrashContent = OCA\Files_Trashbin\Helper::getTrashFiles('/');
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
		return parent::deleteExpiredFiles($files, $limit, 0);
	}

	public function dummyDeleteFiles($files, $availableSpace) {
		return parent::deleteFiles($files, $availableSpace);
	}
}
