<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Versions\Tests;

require_once __DIR__ . '/../appinfo/app.php';

use OC\Files\Storage\Temporary;

/**
 * Class Test_Files_versions
 * this class provide basic files versions test
 *
 * @group DB
 */
class VersioningTest extends \Test\TestCase {

	const TEST_VERSIONS_USER = 'test-versions-user';
	const TEST_VERSIONS_USER2 = 'test-versions-user2';
	const USERS_VERSIONS_ROOT = '/test-versions-user/files_versions';

	/**
	 * @var \OC\Files\View
	 */
	private $rootView;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		$application = new \OCA\Files_Sharing\AppInfo\Application();
		$application->registerMountProviders();

		// create test user
		self::loginHelper(self::TEST_VERSIONS_USER2, true);
		self::loginHelper(self::TEST_VERSIONS_USER, true);
	}

	public static function tearDownAfterClass() {
		// cleanup test user
		$user = \OC::$server->getUserManager()->get(self::TEST_VERSIONS_USER);
		if ($user !== null) { $user->delete(); }
		$user = \OC::$server->getUserManager()->get(self::TEST_VERSIONS_USER2);
		if ($user !== null) { $user->delete(); }

		parent::tearDownAfterClass();
	}

	protected function setUp() {
		parent::setUp();

		$config = \OC::$server->getConfig();
		$mockConfig = $this->getMock('\OCP\IConfig');
		$mockConfig->expects($this->any())
			->method('getSystemValue')
			->will($this->returnCallback(function ($key, $default) use ($config) {
				if ($key === 'filesystem_check_changes') {
					return \OC\Files\Cache\Watcher::CHECK_ONCE;
				} else {
					return $config->getSystemValue($key, $default);
				}
			}));
		$this->overwriteService('AllConfig', $mockConfig);

		// clear hooks
		\OC_Hook::clear();
		\OC::registerShareHooks();
		\OCA\Files_Versions\Hooks::connectHooks();

		self::loginHelper(self::TEST_VERSIONS_USER);
		$this->rootView = new \OC\Files\View();
		if (!$this->rootView->file_exists(self::USERS_VERSIONS_ROOT)) {
			$this->rootView->mkdir(self::USERS_VERSIONS_ROOT);
		}
	}

	protected function tearDown() {
		$this->restoreService('AllConfig');

		if ($this->rootView) {
			$this->rootView->deleteAll(self::TEST_VERSIONS_USER . '/files/');
			$this->rootView->deleteAll(self::TEST_VERSIONS_USER2 . '/files/');
			$this->rootView->deleteAll(self::TEST_VERSIONS_USER . '/files_versions/');
			$this->rootView->deleteAll(self::TEST_VERSIONS_USER2 . '/files_versions/');
		}

		\OC_Hook::clear();

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
			// third set of versions, with some gaps between
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

		$this->runCommands();

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));
	}

	public function testRenameInSharedFolder() {

		\OC\Files\Filesystem::mkdir('folder1');
		\OC\Files\Filesystem::mkdir('folder1/folder2');
		\OC\Files\Filesystem::file_put_contents("folder1/test.txt", "test file");

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

		$node = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER)->get('folder1');
		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_ALL);
		$share = \OC::$server->getShareManager()->createShare($share);

		self::loginHelper(self::TEST_VERSIONS_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists('folder1/test.txt'));

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename('/folder1/test.txt', '/folder1/folder2/test.txt');

		$this->runCommands();

		self::loginHelper(self::TEST_VERSIONS_USER);

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));

		\OC::$server->getShareManager()->deleteShare($share);
	}

	public function testMoveFolder() {

		\OC\Files\Filesystem::mkdir('folder1');
		\OC\Files\Filesystem::mkdir('folder2');
		\OC\Files\Filesystem::file_put_contents('folder1/test.txt', 'test file');

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		// create some versions
		$this->rootView->mkdir(self::USERS_VERSIONS_ROOT . '/folder1');
		$v1 = self::USERS_VERSIONS_ROOT . '/folder1/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/folder1/test.txt.v' . $t2;
		$v1Renamed = self::USERS_VERSIONS_ROOT . '/folder2/folder1/test.txt.v' . $t1;
		$v2Renamed = self::USERS_VERSIONS_ROOT . '/folder2/folder1/test.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename('folder1', 'folder2/folder1');

		$this->runCommands();

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));
	}


	public function testMoveFileIntoSharedFolderAsRecipient() {

		\OC\Files\Filesystem::mkdir('folder1');
		$fileInfo = \OC\Files\Filesystem::getFileInfo('folder1');

		$node = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER)->get('folder1');
		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_ALL);
		$share = \OC::$server->getShareManager()->createShare($share);

		self::loginHelper(self::TEST_VERSIONS_USER2);
		$versionsFolder2 = '/' . self::TEST_VERSIONS_USER2 . '/files_versions';
		\OC\Files\Filesystem::file_put_contents('test.txt', 'test file');

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		$this->rootView->mkdir($versionsFolder2);
		// create some versions
		$v1 = $versionsFolder2 . '/test.txt.v' . $t1;
		$v2 = $versionsFolder2 . '/test.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// move file into the shared folder as recipient
		\OC\Files\Filesystem::rename('/test.txt', '/folder1/test.txt');

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		self::loginHelper(self::TEST_VERSIONS_USER);

		$versionsFolder1 = '/' . self::TEST_VERSIONS_USER . '/files_versions';

		$v1Renamed = $versionsFolder1 . '/folder1/test.txt.v' . $t1;
		$v2Renamed = $versionsFolder1 . '/folder1/test.txt.v' . $t2;

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));

		\OC::$server->getShareManager()->deleteShare($share);
	}

	public function testMoveFolderIntoSharedFolderAsRecipient() {

		\OC\Files\Filesystem::mkdir('folder1');

		$node = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER)->get('folder1');
		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_ALL);
		$share = \OC::$server->getShareManager()->createShare($share);

		self::loginHelper(self::TEST_VERSIONS_USER2);
		$versionsFolder2 = '/' . self::TEST_VERSIONS_USER2 . '/files_versions';
		\OC\Files\Filesystem::mkdir('folder2');
		\OC\Files\Filesystem::file_put_contents('folder2/test.txt', 'test file');

		$t1 = time();
		// second version is two weeks older, this way we make sure that no
		// version will be expired
		$t2 = $t1 - 60 * 60 * 24 * 14;

		$this->rootView->mkdir($versionsFolder2);
		$this->rootView->mkdir($versionsFolder2 . '/folder2');
		// create some versions
		$v1 = $versionsFolder2 . '/folder2/test.txt.v' . $t1;
		$v2 = $versionsFolder2 . '/folder2/test.txt.v' . $t2;

		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		// move file into the shared folder as recipient
		\OC\Files\Filesystem::rename('/folder2', '/folder1/folder2');

		$this->assertFalse($this->rootView->file_exists($v1));
		$this->assertFalse($this->rootView->file_exists($v2));

		self::loginHelper(self::TEST_VERSIONS_USER);

		$versionsFolder1 = '/' . self::TEST_VERSIONS_USER . '/files_versions';

		$v1Renamed = $versionsFolder1 . '/folder1/folder2/test.txt.v' . $t1;
		$v2Renamed = $versionsFolder1 . '/folder1/folder2/test.txt.v' . $t2;

		$this->assertTrue($this->rootView->file_exists($v1Renamed));
		$this->assertTrue($this->rootView->file_exists($v2Renamed));

		\OC::$server->getShareManager()->deleteShare($share);
	}

	public function testRenameSharedFile() {

		\OC\Files\Filesystem::file_put_contents("test.txt", "test file");

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

		$node = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER)->get('test.txt');
		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_SHARE);
		$share = \OC::$server->getShareManager()->createShare($share);

		self::loginHelper(self::TEST_VERSIONS_USER2);

		$this->assertTrue(\OC\Files\Filesystem::file_exists('test.txt'));

		// execute rename hook of versions app
		\OC\Files\Filesystem::rename('test.txt', 'test2.txt');

		self::loginHelper(self::TEST_VERSIONS_USER);

		$this->runCommands();

		$this->assertTrue($this->rootView->file_exists($v1));
		$this->assertTrue($this->rootView->file_exists($v2));

		$this->assertFalse($this->rootView->file_exists($v1Renamed));
		$this->assertFalse($this->rootView->file_exists($v2Renamed));

		\OC::$server->getShareManager()->deleteShare($share);
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

		$this->runCommands();

		$this->assertTrue($this->rootView->file_exists($v1));
		$this->assertTrue($this->rootView->file_exists($v2));

		$this->assertTrue($this->rootView->file_exists($v1Copied));
		$this->assertTrue($this->rootView->file_exists($v2Copied));
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

		$this->assertCount(2, $versions);

		foreach ($versions as $version) {
			$this->assertSame('/subfolder/test.txt', $version['path']);
			$this->assertSame('test.txt', $version['name']);
		}

		//cleanup
		$this->rootView->deleteAll(self::USERS_VERSIONS_ROOT . '/subfolder');
	}

	/**
	 * test if we find all versions and if the versions array contain
	 * the correct 'path' and 'name'
	 */
	public function testGetVersionsEmptyFile() {
		// execute copy hook of versions app
		$versions = \OCA\Files_Versions\Storage::getVersions(self::TEST_VERSIONS_USER, '');
		$this->assertCount(0, $versions);

		$versions = \OCA\Files_Versions\Storage::getVersions(self::TEST_VERSIONS_USER, null);
		$this->assertCount(0, $versions);
	}

	public function testExpireNonexistingFile() {
		$this->logout();
		// needed to have a FS setup (the background job does this)
		\OC_Util::setupFS(self::TEST_VERSIONS_USER);

		$this->assertFalse(\OCA\Files_Versions\Storage::expire('/void/unexist.txt'));
	}

	public function testRestoreSameStorage() {
		\OC\Files\Filesystem::mkdir('sub');
		$this->doTestRestore();
	}

	public function testRestoreCrossStorage() {
		$storage2 = new Temporary(array());
		\OC\Files\Filesystem::mount($storage2, array(), self::TEST_VERSIONS_USER . '/files/sub');

		$this->doTestRestore();
	}

	public function testRestoreNoPermission() {
		$this->loginAsUser(self::TEST_VERSIONS_USER);

		$userHome = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER);
		$node = $userHome->newFolder('folder');
		$file = $node->newFile('test.txt');

		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_READ);
		$share = \OC::$server->getShareManager()->createShare($share);

		$versions = $this->createAndCheckVersions(
			\OC\Files\Filesystem::getView(),
			'folder/test.txt'
		);

		$file->putContent('test file');

		$this->loginAsUser(self::TEST_VERSIONS_USER2);

		$firstVersion = current($versions);

		$this->assertFalse(\OCA\Files_Versions\Storage::rollback('folder/test.txt', $firstVersion['version']), 'Revert did not happen');

		$this->loginAsUser(self::TEST_VERSIONS_USER);

		\OC::$server->getShareManager()->deleteShare($share);
		$this->assertEquals('test file', $file->getContent(), 'File content has not changed');
	}

	/**
	 * @param string $hookName name of hook called
	 * @param string $params variable to receive parameters provided by hook
	 */
	private function connectMockHooks($hookName, &$params) {
		if ($hookName === null) {
			return;
		}

		$eventHandler = $this->getMockBuilder('\stdclass')
			->setMethods(['callback'])
			->getMock();

		$eventHandler->expects($this->any())
			->method('callback')
			->will($this->returnCallback(
				function($p) use (&$params) {
					$params = $p;
				}
			));

		\OCP\Util::connectHook(
			'\OCP\Versions',
			$hookName,
			$eventHandler,
			'callback'
		);
	}

	private function doTestRestore() {
		$filePath = self::TEST_VERSIONS_USER . '/files/sub/test.txt';
		$this->rootView->file_put_contents($filePath, 'test file');

		$t0 = $this->rootView->filemtime($filePath);

		// not exactly the same timestamp as the file
		$t1 = time() - 60;
		// second version is two weeks older
		$t2 = $t1 - 60 * 60 * 24 * 14;

		// create some versions
		$v1 = self::USERS_VERSIONS_ROOT . '/sub/test.txt.v' . $t1;
		$v2 = self::USERS_VERSIONS_ROOT . '/sub/test.txt.v' . $t2;

		$this->rootView->mkdir(self::USERS_VERSIONS_ROOT . '/sub');
		$this->rootView->file_put_contents($v1, 'version1');
		$this->rootView->file_put_contents($v2, 'version2');

		$oldVersions = \OCA\Files_Versions\Storage::getVersions(
			self::TEST_VERSIONS_USER, '/sub/test.txt'
		);

		$this->assertCount(2, $oldVersions);

		$this->assertEquals('test file', $this->rootView->file_get_contents($filePath));
		$info1 = $this->rootView->getFileInfo($filePath);

		$params = array();
		$this->connectMockHooks('rollback', $params);

		$this->assertTrue(\OCA\Files_Versions\Storage::rollback('sub/test.txt', $t2));
		$expectedParams = array(
			'path' => '/sub/test.txt',
		);

		$this->assertEquals($expectedParams['path'], $params['path']);
		$this->assertTrue(array_key_exists('revision', $params));
		$this->assertTrue($params['revision'] > 0);

		$this->assertEquals('version2', $this->rootView->file_get_contents($filePath));
		$info2 = $this->rootView->getFileInfo($filePath);

		$this->assertNotEquals(
			$info2['etag'],
			$info1['etag'],
			'Etag must change after rolling back version'
		);
		$this->assertEquals(
			$info2['fileid'],
			$info1['fileid'],
			'File id must not change after rolling back version'
		);
		$this->assertEquals(
			$info2['mtime'],
			$t2,
			'Restored file has mtime from version'
		);

		$newVersions = \OCA\Files_Versions\Storage::getVersions(
			self::TEST_VERSIONS_USER, '/sub/test.txt'
		);

		$this->assertTrue(
			$this->rootView->file_exists(self::USERS_VERSIONS_ROOT . '/sub/test.txt.v' . $t0),
			'A version file was created for the file before restoration'
		);
		$this->assertTrue(
			$this->rootView->file_exists($v1),
			'Untouched version file is still there'
		);
		$this->assertFalse(
			$this->rootView->file_exists($v2),
			'Restored version file gone from files_version folder'
		);

		$this->assertCount(2, $newVersions, 'Additional version created');

		$this->assertTrue(
			isset($newVersions[$t0 . '#' . 'test.txt']),
			'A version was created for the file before restoration'
		);
		$this->assertTrue(
			isset($newVersions[$t1 . '#' . 'test.txt']),
			'Untouched version is still there'
		);
		$this->assertFalse(
			isset($newVersions[$t2 . '#' . 'test.txt']),
			'Restored version is not in the list any more'
		);
	}

	/**
	 * Test whether versions are created when overwriting as owner
	 */
	public function testStoreVersionAsOwner() {
		$this->loginAsUser(self::TEST_VERSIONS_USER);

		$this->createAndCheckVersions(
			\OC\Files\Filesystem::getView(),
			'test.txt'
		);
	}

	/**
	 * Test whether versions are created when overwriting as share recipient
	 */
	public function testStoreVersionAsRecipient() {
		$this->loginAsUser(self::TEST_VERSIONS_USER);

		\OC\Files\Filesystem::mkdir('folder');
		\OC\Files\Filesystem::file_put_contents('folder/test.txt', 'test file');

		$node = \OC::$server->getUserFolder(self::TEST_VERSIONS_USER)->get('folder');
		$share = \OC::$server->getShareManager()->newShare();
		$share->setNode($node)
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedBy(self::TEST_VERSIONS_USER)
			->setSharedWith(self::TEST_VERSIONS_USER2)
			->setPermissions(\OCP\Constants::PERMISSION_ALL);
		$share = \OC::$server->getShareManager()->createShare($share);

		$this->loginAsUser(self::TEST_VERSIONS_USER2);

		$this->createAndCheckVersions(
			\OC\Files\Filesystem::getView(),
			'folder/test.txt'
		);

		\OC::$server->getShareManager()->deleteShare($share);
	}

	/**
	 * Test whether versions are created when overwriting anonymously.
	 *
	 * When uploading through a public link or publicwebdav, no user
	 * is logged in. File modification must still be able to find
	 * the owner and create versions.
	 */
	public function testStoreVersionAsAnonymous() {
		$this->logout();

		// note: public link upload does this,
		// needed to make the hooks fire
		\OC_Util::setupFS(self::TEST_VERSIONS_USER);

		$userView = new \OC\Files\View('/' . self::TEST_VERSIONS_USER . '/files');
		$this->createAndCheckVersions(
			$userView,
			'test.txt'
		);
	}

	/**
	 * @param \OC\Files\View $view
	 * @param string $path
	 */
	private function createAndCheckVersions(\OC\Files\View $view, $path) {
		$view->file_put_contents($path, 'test file');
		$view->file_put_contents($path, 'version 1');
		$view->file_put_contents($path, 'version 2');

		$this->loginAsUser(self::TEST_VERSIONS_USER);

		// need to scan for the versions
		list($rootStorage,) = $this->rootView->resolvePath(self::TEST_VERSIONS_USER . '/files_versions');
		$rootStorage->getScanner()->scan('files_versions');

		$versions = \OCA\Files_Versions\Storage::getVersions(
			self::TEST_VERSIONS_USER, '/' . $path
		);

		// note: we cannot predict how many versions are created due to
		// test run timing
		$this->assertGreaterThan(0, count($versions));

		return $versions;
	}

	/**
	 * @param string $user
	 * @param bool $create
	 */
	public static function loginHelper($user, $create = false) {

		if ($create) {
			$backend  = new \Test\Util\User\Dummy();
			$backend->createUser($user, $user);
			\OC::$server->getUserManager()->registerBackend($backend);
		}

		$storage = new \ReflectionClass('\OC\Files\Storage\Shared');
		$isInitialized = $storage->getProperty('initialized');
		$isInitialized->setAccessible(true);
		$isInitialized->setValue($storage, false);
		$isInitialized->setAccessible(false);

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_User::setUserId($user);
		\OC_Util::setupFS($user);
		\OC::$server->getUserFolder($user);
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
