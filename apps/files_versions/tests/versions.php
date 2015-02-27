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

require_once __DIR__ . '/../appinfo/app.php';

/**
 * Class Test_Files_versions
 * this class provide basic files versions test
 */
class Test_Files_Versioning extends \Test\TestCase {

	const TEST_VERSIONS_USER = 'test-versions-user';
	const TEST_VERSIONS_USER2 = 'test-versions-user2';
	const USERS_VERSIONS_ROOT = '/test-versions-user/files_versions';

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		// clear share hooks
		\OC_Hook::clear('OCP\\Share');
		\OC::registerShareHooks();
		\OCA\Files_Versions\Hooks::connectHooks();
		\OCP\Util::connectHook('OC_Filesystem', 'setup', '\OC\Files\Storage\Shared', 'setup');

		// create test user
		self::loginHelper(self::TEST_VERSIONS_USER2, true);
		self::loginHelper(self::TEST_VERSIONS_USER, true);
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_VERSIONS_USER);
		\OC_User::deleteUser(self::TEST_VERSIONS_USER2);

		parent::tearDownAfterClass();
	}

	protected function setUp() {
		parent::setUp();

		self::loginHelper(self::TEST_VERSIONS_USER);
		$this->rootView = new \OC\Files\View();
		if (!$this->rootView->file_exists(self::USERS_VERSIONS_ROOT)) {
			$this->rootView->mkdir(self::USERS_VERSIONS_ROOT);
		}
	}

	protected function tearDown() {
		$this->rootView->deleteAll(self::USERS_VERSIONS_ROOT);

		parent::tearDown();
	}

	/**
	 * @medium
	 * test expire logic
	 * @dataProvider versionsProvider
	 */
	public function testGetExpireList($versions, $sizeOfAllDeletedFiles) {

		// last interval end at 2592000
		$startTime = 5000000;

		$testClass = new VersionStorageToTest();
		list($deleted, $size) = $testClass->callProtectedGetExpireList($startTime, $versions);

		// we should have deleted 16 files each of the size 1
		$this->assertEquals($sizeOfAllDeletedFiles, $size);

		// the deleted array should only contain versions which should be deleted
		foreach($deleted as $key => $path) {
			unset($versions[$key]);
			$this->assertEquals("delete", substr($path, 0, strlen("delete")));
		}

		// the versions array should only contain versions which should be kept
		foreach ($versions as $version) {
			$this->assertEquals("keep", $version['path']);
		}

	}

	public function versionsProvider() {
		return array(
			// first set of versions uniformly distributed versions
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					array("version" => 4999999, "path" => "keep", "size" => 1),
					array("version" => 4999998, "path" => "delete", "size" => 1),
					array("version" => 4999997, "path" => "keep", "size" => 1),
					array("version" => 4999995, "path" => "keep", "size" => 1),
					array("version" => 4999994, "path" => "delete", "size" => 1),
					//next slice (60sec) starts at 4999990 keep one version every 10 secons
					array("version" => 4999988, "path" => "keep", "size" => 1),
					array("version" => 4999978, "path" => "keep", "size" => 1),
					array("version" => 4999975, "path" => "delete", "size" => 1),
					array("version" => 4999972, "path" => "delete", "size" => 1),
					array("version" => 4999967, "path" => "keep", "size" => 1),
					array("version" => 4999958, "path" => "delete", "size" => 1),
					array("version" => 4999957, "path" => "keep", "size" => 1),
					//next slice (3600sec) start at 4999940 keep one version every 60 seconds
					array("version" => 4999900, "path" => "keep", "size" => 1),
					array("version" => 4999841, "path" => "delete", "size" => 1),
					array("version" => 4999840, "path" => "keep", "size" => 1),
					array("version" => 4999780, "path" => "keep", "size" => 1),
					array("version" => 4996401, "path" => "keep", "size" => 1),
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4992800, "path" => "keep", "size" => 1),
					array("version" => 4989800, "path" => "delete", "size" => 1),
					array("version" => 4989700, "path" => "delete", "size" => 1),
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				16 // size of all deleted files (every file has the size 1)
			),
			// second set of versions, here we have only really old versions
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					// next slice (60sec) starts at 4999990 keep one version every 10 secons
					// next slice (3600sec) start at 4999940 keep one version every 60 seconds
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4996400, "path" => "keep", "size" => 1),
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4996350, "path" => "delete", "size" => 1),
					array("version" => 4992800, "path" => "keep", "size" => 1),
					array("version" => 4989800, "path" => "delete", "size" => 1),
					array("version" => 4989700, "path" => "delete", "size" => 1),
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				11 // size of all deleted files (every file has the size 1)
			),
			// third set of versions, with some gaps inbetween
			array(
				array(
					// first slice (10sec) keep one version every 2 seconds
					array("version" => 4999999, "path" => "keep", "size" => 1),
					array("version" => 4999998, "path" => "delete", "size" => 1),
					array("version" => 4999997, "path" => "keep", "size" => 1),
					array("version" => 4999995, "path" => "keep", "size" => 1),
					array("version" => 4999994, "path" => "delete", "size" => 1),
					//next slice (60sec) starts at 4999990 keep one version every 10 secons
					array("version" => 4999988, "path" => "keep", "size" => 1),
					array("version" => 4999978, "path" => "keep", "size" => 1),
					//next slice (3600sec) start at 4999940 keep one version every 60 seconds
					// next slice (86400sec) start at 4996400 keep one version every 3600 seconds
					array("version" => 4989200, "path" => "keep", "size" => 1),
					// next slice (2592000sec) start at 4913600 keep one version every 86400 seconds
					array("version" => 4913600, "path" => "keep", "size" => 1),
					array("version" => 4852800, "path" => "delete", "size" => 1),
					array("version" => 4827201, "path" => "delete", "size" => 1),
					array("version" => 4827200, "path" => "keep", "size" => 1),
					array("version" => 4777201, "path" => "delete", "size" => 1),
					array("version" => 4777501, "path" => "delete", "size" => 1),
					array("version" => 4740000, "path" => "keep", "size" => 1),
					// final slice starts at 2408000 keep one version every 604800 secons
					array("version" => 2408000, "path" => "keep", "size" => 1),
					array("version" => 1803201, "path" => "delete", "size" => 1),
					array("version" => 1803200, "path" => "keep", "size" => 1),
					array("version" => 1800199, "path" => "delete", "size" => 1),
					array("version" => 1800100, "path" => "delete", "size" => 1),
					array("version" => 1198300, "path" => "keep", "size" => 1),
				),
				9 // size of all deleted files (every file has the size 1)
			),

		);
	}

	public function testRename() {

		\OC\Files\Filesystem::file_put_contents("test.txt", "test file");

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t2;
		$v1Renamed = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t1;
		$v2Renamed = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename("test.txt", "test2.txt");

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));

		//cleanup
		\OC\Files\Filesystem::unlink('test2.txt');
	}

	public function testRenameInSharedFolder() {

		\OC\Files\Filesystem::mkdir('folder1');
		\OC\Files\Filesystem::mkdir('folder1/folder2');
		\OC\Files\Filesystem::file_put_contents("folder1/test.txt", "test file");

		$fileInfo = \OC\Files\Filesystem::getFileInfo('folder1');

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		$this->rootView->mkdir(self::USERS_VERSIONS_ROOT . '/folder1');
		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/folder1/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/folder1/test.txt.v' . $t2;
		$v1Renamed = self::USERS_VERSIONS_ROOT . '/folder1/folder2/test.txt.v' . $t1;
		$v2Renamed = self::USERS_VERSIONS_ROOT . '/folder1/folder2/test.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		\OCP\Share::shareItem('folder', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_VERSIONS_USER2, \OCP\Constants::PERMISSION_ALL);

		self::loginHelper(self::TEST_VERSIONS_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists('folder1/test.txt'));

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename('/folder1/test.txt', '/folder1/folder2/test.txt');

		self::loginHelper(self::TEST_VERSIONS_USER2);

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));

		//cleanup
		\OC\Files\Filesystem::unlink('/folder1/folder2/test.txt');
	}

	public function testRenameSharedFile() {

		\OC\Files\Filesystem::file_put_contents("test.txt", "test file");

		$fileInfo = \OC\Files\Filesystem::getFileInfo('test.txt');

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		$this->rootView->mkdir(self::USERS_VERSIONS_ROOT);
		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t2;
		// the renamed versions should not exist! Because we only moved the mount point!
		$v1Renamed = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t1;
		$v2Renamed = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		\OCP\Share::shareItem('file', $fileInfo['fileid'], \OCP\Share::SHARE_TYPE_USER, self::TEST_VERSIONS_USER2, \OCP\Constants::PERMISSION_ALL);

		self::loginHelper(self::TEST_VERSIONS_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists('test.txt'));

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename('test.txt', 'test2.txt');

		self::loginHelper(self::TEST_VERSIONS_USER);

		$this->assertTrue($this->rootView->file_exists($v1));
		$this->assertTrue($this->rootView->file_exists($v2));

		$this->assertFalse($this->rootView->file_exists($v1Renamed));
		$this->assertFalse($this->rootView->file_exists($v2Renamed));

		//cleanup
		\OC\Files\Filesystem::unlink('/test.txt');
	}

	public function testCopy() {

		\OC\Files\Filesystem::file_put_contents("test.txt", "test file");

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/test.txt.v' . $t2;
		$v1Copied = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t1;
		$v2Copied = self::USERS_VERSIONS_ROOT . '/test2.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// execute copy hook of versions app
		\OC\Files\Filesystem::copy("test.txt", "test2.txt");

		$this->assertTrue($this->rootView->file_exists($v1));
		$this->assertTrue($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Copied));
		$this->assertTrue($this->rootView->file_exists($v2Copied));

		//cleanup
		\OC\Files\Filesystem::unlink('test.txt');
		\OC\Files\Filesystem::unlink('test2.txt');
	}

	/**
	 * test if we find all versions and if the versions array contain
	 * the correct 'path' and 'name'
	 */
	public function testGetVersions() {

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/subfolder/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/subfolder/test.txt.v' . $t2;

		$this->rootView->mkdir(self::USERS_VERSIONS_ROOT . '/subfolder/');

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// execute copy hook of versions app
		$versions = \OCA\Files_Versions\Storage::getVersions(self::TEST_VERSIONS_USER, '/subfolder/test.txt');

		$this->assertSame(2, count($versions));

		foreach ($versions as $version) {
			$this->assertSame('/subfolder/test.txt', $version['path']);
			$this->assertSame('test.txt', $version['name']);
		}

		//cleanup
		$this->rootView->deleteAll(self::USERS_VERSIONS_ROOT . '/subfolder');
	}

	/**
	 * @param string $user
	 * @param bool $create
	 * @param bool $password
	 */
	public static function loginHelper($user, $create = false) {

		if ($create) {
			\OC_User::createUser($user, $user);
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
	}

}

// extend the original class to make it possible to test protected methods
class VersionStorageToTest extends \OCA\Files_Versions\Storage {

	/**
	 * @param integer $time
	 */
	public function callProtectedGetExpireList($time, $versions) {
		return self::getExpireList($time, $versions);

	}
}
