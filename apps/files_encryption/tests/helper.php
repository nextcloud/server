<?php
/**
 * Copyright (c) 2013 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Helper
 */
class Test_Encryption_Helper extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_HELPER_USER1 = "test-helper-user1";
	const TEST_ENCRYPTION_HELPER_USER2 = "test-helper-user2";

	public function setUp() {
		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Helper::TEST_ENCRYPTION_HELPER_USER2, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Helper::TEST_ENCRYPTION_HELPER_USER1, true);
	}

	public function tearDown() {
		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Helper::TEST_ENCRYPTION_HELPER_USER1);
		\OC_User::deleteUser(\Test_Encryption_Helper::TEST_ENCRYPTION_HELPER_USER2);
	}

	public static function tearDownAfterClass() {

		\OC_Hook::clear();
		\OC_FileProxy::clearProxies();

		// Delete keys in /data/
		$view = new \OC\Files\View('/');
		$view->rmdir('public-keys');
		$view->rmdir('owncloud_private_key');
	}

	/**
	 * @medium
	 */
	function testStripPartialFileExtension() {

		$partFilename = 'testfile.txt.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Encryption\Helper::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Encryption\Helper::stripPartialFileExtension($partFilename));

		$this->assertFalse(Encryption\Helper::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Encryption\Helper::stripPartialFileExtension($filename));
	}


	/**
	 * @medium
	 */
	function testStripPartialFileExtensionWithTransferIdPath() {

		$partFilename = 'testfile.txt.ocTransferId643653835.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Encryption\Helper::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Encryption\Helper::stripPartialFileExtension($partFilename));

		$this->assertFalse(Encryption\Helper::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Encryption\Helper::stripPartialFileExtension($filename));
	}

	function testGetPathToRealFile() {

		// the relative path to /user/files/ that's what we want to get from getPathToRealFile()
		$relativePath = "foo/bar/test.txt";

		// test paths
		$versionPath = "/user/files_versions/foo/bar/test.txt.v456756835";
		$cachePath = "/user/cache/transferid636483/foo/bar/test.txt";

		$this->assertEquals($relativePath, Encryption\Helper::getPathToRealFile($versionPath));
		$this->assertEquals($relativePath, Encryption\Helper::getPathToRealFile($cachePath));
	}

	function testGetUser() {

		$path1 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/files/foo/bar.txt";
		$path2 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/cache/foo/bar.txt";
		$path3 = "/" . self::TEST_ENCRYPTION_HELPER_USER2 . "/thumbnails/foo";
		$path4 ="/" . "/" . self::TEST_ENCRYPTION_HELPER_USER1;

		\Test_Encryption_Util::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1);

		// if we are logged-in every path should return the currently logged-in user
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Encryption\Helper::getUser($path3));

		// now log out
		\Test_Encryption_Util::logoutHelper();

		// now we should only get the user from /user/files and user/cache paths
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Encryption\Helper::getUser($path1));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Encryption\Helper::getUser($path2));

		$this->assertFalse(Encryption\Helper::getUser($path3));
		$this->assertFalse(Encryption\Helper::getUser($path4));

		// Log-in again
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Helper::TEST_ENCRYPTION_HELPER_USER1);
	}

	function userNamesProvider() {
		return array(
			array('testuser' . uniqid()),
			array('user.name.with.dots'),
		);
	}

	/**
	 * Tests whether share keys can be found
	 *
	 * @dataProvider userNamesProvider
	 */
	function testFindShareKeys($userName) {
		// note: not using dataProvider as we want to make
		// sure that the correct keys are match and not any
		// other ones that might happen to have similar names
		\Test_Encryption_Util::setupHooks();
		\Test_Encryption_Util::loginHelper($userName, true);
		$testDir = 'testFindShareKeys' . uniqid() . '/';
		$baseDir = $userName . '/files/' . $testDir;
		$fileList = array(
			't est.txt',
			't est_.txt',
			't est.doc.txt',
			't est(.*).txt', // make sure the regexp is escaped
			'multiple.dots.can.happen.too.txt',
			't est.' . $userName . '.txt',
			't est_.' . $userName . '.shareKey.txt',
			'who would upload their.shareKey',
			'user ones file.txt',
			'user ones file.txt.backup',
			'.t est.txt'
		);

		$rootView = new \OC\Files\View('/');
		$rootView->mkdir($baseDir);
		foreach ($fileList as $fileName) {
			$rootView->file_put_contents($baseDir . $fileName, 'dummy');
		}

		$shareKeysDir = $userName . '/files_encryption/share-keys/' . $testDir;
		foreach ($fileList as $fileName) {
			// make sure that every file only gets its correct respective keys
			$result = Encryption\Helper::findShareKeys($baseDir . $fileName, $shareKeysDir . $fileName, $rootView);
			$this->assertEquals(
				array($shareKeysDir . $fileName . '.' . $userName . '.shareKey'),
				$result
			);
		}
	}

}
