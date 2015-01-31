<?php
/**
 * Copyright (c) 2013 Bjoern Schiessle <schiessle@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Encryption\Tests;

use OCA\Files_Encryption;
use OCA\Files_Encryption\Helper;

/**
 * Class Helper
 */
class TestHelper extends TestCase {

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
		\OC_FileProxy::register(new Files_Encryption\Proxy());
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

	/**
	 * @dataProvider dataVersionsPathPositive
	 */
	function testGetPathFromVersionPositive($path, $expected) {
		$result = Helper::getPathFromVersion($path);
		$this->assertSame($expected, $result);
	}

	function dataVersionsPathPositive() {
		return array(
			array('/user/files_versions/foo/bar/test.txt.v456756835', 'foo/bar/test.txt'),
			array('user/files_versions/foo/bar/test.txt.v456756835', 'foo/bar/test.txt'),
			array('user/files_versions//foo/bar/test.txt.v456756835', 'foo/bar/test.txt'),
			array('user/files_versions/test.txt.v456756835', 'test.txt'),
		);
	}

	/**
	 * @dataProvider dataVersionsPathNegative
	 * @expectedException \OCA\Files_Encryption\Exception\EncryptionException
	 */
	function testGetPathFromVersionNegative($path) {
		Helper::getPathFromVersion($path);
	}

	function dataVersionsPathNegative() {
		return array(
			array('/user/files_versions/'),
			array('/user/files_versions'),
		);
	}

	/**
	 * @dataProvider dataPathsCachedFilePositive
	 */
	function testGetPathFromCachedFilePositive($path, $expected) {
		$result = Helper::getPathFromCachedFile($path);
		$this->assertEquals($expected, $result);
	}

	function dataPathsCachedFilePositive() {
		return array(
			array('/user/cache/transferid636483/foo/bar/test.txt', 'foo/bar/test.txt'),
			array('/user/cache/transferid636483//test.txt', 'test.txt'),
			array('user/cache/transferid636483//test.txt', 'test.txt'),
		);
	}


	/**
	 * @dataProvider dataPathsCachedFileNegative
	 * @expectedException \OCA\Files_Encryption\Exception\EncryptionException
	 */
	function testGetPathFromCachedFileNegative($path) {
		Helper::getPathFromCachedFile($path);
	}

	function dataPathsCachedFileNegative() {
		return array(
			array('/user/cache/transferid636483/'),
			array('/user/cache/transferid636483'),
			array('/user/cache/transferid636483//'),
			array('/user/cache'),
		);
	}

	function testGetUser() {
		self::setUpUsers();

		$path1 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/files/foo/bar.txt";
		$path2 = "/" . self::TEST_ENCRYPTION_HELPER_USER1 . "/cache/foo/bar.txt";
		$path3 = "/" . self::TEST_ENCRYPTION_HELPER_USER2 . "/thumbnails/foo";
		$path4 ="/" . "/" . self::TEST_ENCRYPTION_HELPER_USER1;

		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1);

		// if we are logged-in every path should return the currently logged-in user
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path1));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path2));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path3));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path4));

		// now log out
		self::logoutHelper();

		// now we should only get the user from /user/files and user/cache paths
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path1));
		$this->assertEquals(self::TEST_ENCRYPTION_HELPER_USER1, Helper::getUser($path2));

		try {
			$this->assertFalse(Helper::getUser($path3));
			$this->assertFalse(true, '"OCA\Files_Encryption\Exception\EncryptionException: Could not determine user expected"');
		} catch (Files_Encryption\Exception\EncryptionException $e) {
			$this->assertSame('Could not determine user', $e->getMessage());
		}
		try {
			$this->assertFalse(Helper::getUser($path4));
			$this->assertFalse(true, '"OCA\Files_Encryption\Exception\EncryptionException: Could not determine user expected"');
		} catch (Files_Encryption\Exception\EncryptionException $e) {
			$this->assertSame('Could not determine user', $e->getMessage());
		}

		// Log-in again
		self::loginHelper(self::TEST_ENCRYPTION_HELPER_USER1);
		self::cleanUpUsers();
	}

	/**
	 * @dataProvider dataStripUserFilesPath
	 */
	function testStripUserFilesPath($path, $expected) {
		$result = Helper::stripUserFilesPath($path);
		$this->assertSame($expected, $result);
	}

	function dataStripUserFilesPath() {
		return array(
			array('/user/files/foo.txt', 'foo.txt'),
			array('//user/files/foo.txt', 'foo.txt'),
			array('user//files/foo/bar.txt', 'foo/bar.txt'),
			array('user//files/', false),
			array('/user', false),
			array('', false),
		);
	}

	/**
	 * @dataProvider dataStripUserFilesPathPositive
	 */
	function testGetUserFromPathPositive($path, $expected) {
		self::setUpUsers();
		$result = Helper::getUserFromPath($path);
		$this->assertSame($expected, $result);
		self::cleanUpUsers();
	}

	function dataStripUserFilesPathPositive() {
		return array(
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '/files/foo.txt', self::TEST_ENCRYPTION_HELPER_USER1),
			array('//' . self::TEST_ENCRYPTION_HELPER_USER2 . '/files_versions/foo.txt', self::TEST_ENCRYPTION_HELPER_USER2),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '/files_trashbin/', self::TEST_ENCRYPTION_HELPER_USER1),
			array(self::TEST_ENCRYPTION_HELPER_USER1 . '//cache/foo/bar.txt', self::TEST_ENCRYPTION_HELPER_USER1),
		);
	}

	/**
	 * @dataProvider dataStripUserFilesPathNegative
	 * @expectedException \OCA\Files_Encryption\Exception\EncryptionException
	 */
	function testGetUserFromPathNegative($path) {
		Helper::getUserFromPath($path);
	}

	function dataStripUserFilesPathNegative() {
		return array(
			array('/unknown_user/files/foo.txt'),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER2 . '/unknown_folder/foo.txt'),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1),
			array(''),
		);
	}

	/**
	 * @dataProvider dataPaths
	 */
	function testMkdirr($path, $expected) {
		self::setUpUsers();
		Helper::mkdirr($path, new \OC\Files\View('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '/files'));
		// ignore the filename because we only check for the directories
		$dirParts = array_slice($expected, 0, -1);
		$expectedPath = implode('/', $dirParts);
		$this->assertTrue(\OC\Files\Filesystem::is_dir($expectedPath));

		// cleanup
		\OC\Files\Filesystem::unlink('/' . $expected[0]);
		self::cleanUpUsers();
	}

	/**
	 * @dataProvider dataDetectFileTypePositive
	 */
	function testDetectFileTypePositive($path, $expected) {
		$result = Helper::detectFileType($path);
		$this->assertSame($expected, $result);
	}

	function dataDetectFileTypePositive() {
		return array(
			array(self::TEST_ENCRYPTION_HELPER_USER1 . '/files', Files_Encryption\Util::FILE_TYPE_FILE),
			array(self::TEST_ENCRYPTION_HELPER_USER1 . '/files/foo/bar', Files_Encryption\Util::FILE_TYPE_FILE),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '/files/foo/bar', Files_Encryption\Util::FILE_TYPE_FILE),
			array(self::TEST_ENCRYPTION_HELPER_USER1 . '/files_versions', Files_Encryption\Util::FILE_TYPE_VERSION),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '//files_versions/foo/bar', Files_Encryption\Util::FILE_TYPE_VERSION),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '//cache/foo/bar', Files_Encryption\Util::FILE_TYPE_CACHE),
		);
	}

	/**
	 * @dataProvider dataDetectFileTypeNegative
	 * @expectedException \OCA\Files_Encryption\Exception\EncryptionException
	 */
	function testDetectFileTypeNegative($path) {
		Helper::detectFileType($path);
	}

	function dataDetectFileTypeNegative() {
		return array(
			array('/files'),
			array('/' . self::TEST_ENCRYPTION_HELPER_USER1 . '/unsuported_dir/foo/bar'),
		);
	}

	/**
	 * @dataProvider dataPaths
	 */
	function testSplitPath($path, $expected) {
		$result = Helper::splitPath($path);
		$this->compareArray($result, $expected);
	}

	function dataPaths() {
		return array(
			array('foo/bar/test.txt', array('', 'foo', 'bar', 'test.txt')),
			array('/foo/bar/test.txt', array('', 'foo', 'bar', 'test.txt')),
			array('/foo/bar//test.txt', array('', 'foo', 'bar', 'test.txt')),
			array('//foo/bar/test.txt', array('', 'foo', 'bar', 'test.txt')),
			array('foo', array('', 'foo')),
			array('/foo', array('', 'foo')),
			array('//foo', array('', 'foo')),
		);
	}

	function compareArray($result, $expected) {
		$this->assertSame(count($expected), count($result));

		foreach ($expected as $key => $value) {
			$this->assertArrayHasKey($key, $result);
			$this->assertSame($value, $result[$key]);
		}
	}

}
