<?php
/**
 * Copyright (c) 2013 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OCA\Files_Encryption\Helper;

/**
 * Class Test_Encryption_Helper
 */
class Test_Encryption_Helper extends \OCA\Files_Encryption\Tests\TestCase {

	const TEST_ENCRYPTION_HELPER_USER1 = "test-helper-user1";
	const TEST_ENCRYPTION_HELPER_USER2 = "test-helper-user2";

	protected function setUpUsers() {
		// create test user
		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER2, true);
		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1, true);
	}

	protected  function cleanUpUsers() {
		// cleanup test user
		\OC_User::deleteUser(self::TEST_ENCRYPTION_HELPER_USER1);
		\OC_User::deleteUser(self::TEST_ENCRYPTION_HELPER_USER2);
	}

	public static function setupHooks() {
		// Filesystem related hooks
		Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new \OCA\Files_Encryption\Proxy());
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
	}

	/**
	 * @medium
	 */
	function testStripPartialFileExtension() {

		$partFilename = 'testfile.txt.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Helper::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Helper::stripPartialFileExtension($partFilename));

		$this->assertFalse(Helper::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Helper::stripPartialFileExtension($filename));
	}


	/**
	 * @medium
	 */
	function testStripPartialFileExtensionWithTransferIdPath() {

		$partFilename = 'testfile.txt.ocTransferId643653835.part';
		$filename = 'testfile.txt';

		$this->assertTrue(Helper::isPartialFilePath($partFilename));

		$this->assertEquals('testfile.txt', Helper::stripPartialFileExtension($partFilename));

		$this->assertFalse(Helper::isPartialFilePath($filename));

		$this->assertEquals('testfile.txt', Helper::stripPartialFileExtension($filename));
	}

	function testGetPathToRealFile() {

		// the relative path to /user/files/ that's what we want to get from getPathToRealFile()
		$relativePath = "foo/bar/test.txt";

		// test paths
		$versionPath = "/user/files_versions/foo/bar/test.txt.v456756835";
		$cachePath = "/user/cache/transferid636483/foo/bar/test.txt";

		$this->assertEquals($relativePath, Helper::getPathToRealFile($versionPath));
		$this->assertEquals($relativePath, Helper::getPathToRealFile($cachePath));
	}

	function testGetUser() {
		self::setUpUsers();

		$path1 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/files/foo/bar.txt";
		$path2 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/cache/foo/bar.txt";
		$path3 = "/" . self::TEST_ENCRYPTION_HELPER_USER2 . "/thumbnails/foo";
		$path4 ="/" . "/" . self::TEST_ENCRYPTION_HELPER_USER1;

		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1);

		// if we are logged-in every path should return the currently logged-in user
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path3));

		// now log out
		self::logoutHelper();

		// now we should only get the user from /user/files and user/cache paths
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path1));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path2));

		$this->assertFalse(Helper::getUser($path3));
		$this->assertFalse(Helper::getUser($path4));

		// Log-in again
		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1);
		self::cleanUpUsers();
	}

}
