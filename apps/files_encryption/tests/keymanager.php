<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../lib/crypt.php';
require_once __DIR__ . '/../lib/keymanager.php';
require_once __DIR__ . '/../lib/proxy.php';
require_once __DIR__ . '/../lib/stream.php';
require_once __DIR__ . '/../lib/util.php';
require_once __DIR__ . '/../lib/helper.php';
require_once __DIR__ . '/../appinfo/app.php';
require_once __DIR__ . '/util.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Keymanager
 */
class Test_Encryption_Keymanager extends \PHPUnit_Framework_TestCase {

	const TEST_USER = "test-keymanager-user";

	public $userId;
	public $pass;
	public static $stateFilesTrashbin;
	/**
	 * @var OC\Files\View
	 */
	public $view;
	public $randomKey;
	public $dataShort;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// disable file proxy by default
		\OC_FileProxy::$enabled = false;

		// remember files_trashbin state
		self::$stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');

		// create test user
		\OC_User::deleteUser(\Test_Encryption_Keymanager::TEST_USER);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Keymanager::TEST_USER, true);
	}

	function setUp() {
		// set content for encrypting / decrypting in tests
		$this->dataLong = file_get_contents(__DIR__ . '/../lib/crypt.php');
		$this->dataShort = 'hats';
		$this->dataUrl = __DIR__ . '/../lib/crypt.php';
		$this->legacyData = __DIR__ . '/legacy-text.txt';
		$this->legacyEncryptedData = __DIR__ . '/legacy-encrypted-text.txt';
		$this->randomKey = Encryption\Crypt::generateKey();

		$keypair = Encryption\Crypt::createKeypair();
		$this->genPublicKey = $keypair['publicKey'];
		$this->genPrivateKey = $keypair['privateKey'];

		$this->view = new \OC\Files\View('/');

		\Test_Encryption_Util::loginHelper(Test_Encryption_Keymanager::TEST_USER);
		$this->userId = \Test_Encryption_Keymanager::TEST_USER;
		$this->pass = \Test_Encryption_Keymanager::TEST_USER;

		$userHome = \OC_User::getHome($this->userId);
		$this->dataDir = str_replace('/' . $this->userId, '', $userHome);
	}

	function tearDown() {
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys');
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles');
	}

	public static function tearDownAfterClass() {
		\OC_FileProxy::$enabled = true;

		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Keymanager::TEST_USER);
		// reset app files_trashbin
		if (self::$stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		}
	}

	/**
	 * @medium
	 */
	function testGetPrivateKey() {

		$key = Encryption\Keymanager::getPrivateKey($this->view, $this->userId);

		$privateKey = Encryption\Crypt::decryptPrivateKey($key, $this->pass);

		$res = openssl_pkey_get_private($privateKey);

		$this->assertTrue(is_resource($res));

		$sslInfo = openssl_pkey_get_details($res);

		$this->assertArrayHasKey('key', $sslInfo);

	}

	/**
	 * @medium
	 */
	function testGetPublicKey() {

		$publiceKey = Encryption\Keymanager::getPublicKey($this->view, $this->userId);

		$res = openssl_pkey_get_public($publiceKey);

		$this->assertTrue(is_resource($res));

		$sslInfo = openssl_pkey_get_details($res);

		$this->assertArrayHasKey('key', $sslInfo);
	}

	/**
	 * @small
	 */
	function testGetFilenameFromShareKey() {
		$this->assertEquals("file",
				\TestProtectedKeymanagerMethods::testGetFilenameFromShareKey("file.user.shareKey"));
		$this->assertEquals("file.name.with.dots",
				\TestProtectedKeymanagerMethods::testGetFilenameFromShareKey("file.name.with.dots.user.shareKey"));
		$this->assertFalse(\TestProtectedKeymanagerMethods::testGetFilenameFromShareKey("file.txt"));
	}

	/**
	 * @medium
	 */
	function testSetFileKey() {

		$key = $this->randomKey;

		$file = 'unittest-' . uniqid() . '.txt';

		$util = new Encryption\Util($this->view, $this->userId);

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$this->view->file_put_contents($this->userId . '/files/' . $file, $this->dataShort);

		Encryption\Keymanager::setFileKey($this->view, $util, $file, $key);

		$this->assertTrue($this->view->file_exists('/' . $this->userId . '/files_encryption/keyfiles/' . $file . '.key'));

		// cleanup
		$this->view->unlink('/' . $this->userId . '/files/' . $file);

		// change encryption proxy to previous state
		\OC_FileProxy::$enabled = $proxyStatus;
	}

	/**
	 * @medium
	 */
	function testSetPrivateKey() {

		$key = "dummy key";

		Encryption\Keymanager::setPrivateKey($key, 'dummyUser');

		$this->assertTrue($this->view->file_exists('/dummyUser/files_encryption/dummyUser.private.key'));

		//clean up
		$this->view->deleteAll('/dummyUser');
	}

	/**
	 * @medium
	 */
	function testSetPrivateSystemKey() {

		$key = "dummy key";
		$keyName = "myDummyKey.private.key";

		Encryption\Keymanager::setPrivateSystemKey($key, $keyName);

		$this->assertTrue($this->view->file_exists('/owncloud_private_key/' . $keyName));

		// clean up
		$this->view->unlink('/owncloud_private_key/' . $keyName);
	}


	/**
	 * @medium
	 */
	function testGetUserKeys() {

		$keys = Encryption\Keymanager::getUserKeys($this->view, $this->userId);

		$resPublic = openssl_pkey_get_public($keys['publicKey']);

		$this->assertTrue(is_resource($resPublic));

		$sslInfoPublic = openssl_pkey_get_details($resPublic);

		$this->assertArrayHasKey('key', $sslInfoPublic);

		$privateKey = Encryption\Crypt::decryptPrivateKey($keys['privateKey'], $this->pass);

		$resPrivate = openssl_pkey_get_private($privateKey);

		$this->assertTrue(is_resource($resPrivate));

		$sslInfoPrivate = openssl_pkey_get_details($resPrivate);

		$this->assertArrayHasKey('key', $sslInfoPrivate);
	}

	/**
	 * @medium
	 */
	function testRecursiveDelShareKeysFolder() {

		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/existingFile.txt', 'data');

		// create folder structure for some dummy share key files
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1');
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder');
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder');

		// create some dummy share keys
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file1.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file2.user2.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file2.user3.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/file2.user3.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file1.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file2.user2.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file2.user3.shareKey', 'data');

		// recursive delete share keys from user1 and user2
		Encryption\Keymanager::delShareKey($this->view, array('user1', 'user2', Test_Encryption_Keymanager::TEST_USER), '/folder1/', Test_Encryption_Keymanager::TEST_USER);

		// check if share keys from user1 and user2 are deleted
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.user1.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file1.user1.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file2.user2.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file1.user1.shareKey'));
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file2.user2.shareKey'));

		// check if share keys from user3 still exists
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/file2.user3.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/subsubfolder/file2.user3.shareKey'));
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/subfolder/file2.user3.shareKey'));

		// owner key from existing file should still exists because the file is still there
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey'));

		// cleanup
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');

	}

	/**
	 * @medium
	 */
	function testRecursiveDelShareKeysFile() {

		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/existingFile.txt', 'data');

		// create folder structure for some dummy share key files
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1');

		// create some dummy share keys
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user2.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user3.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey', 'data');

		// recursive delete share keys from user1 and user2
		Encryption\Keymanager::delShareKey($this->view, array('user1', 'user2', Test_Encryption_Keymanager::TEST_USER), '/folder1/existingFile.txt', Test_Encryption_Keymanager::TEST_USER);

		// check if share keys from user1 and user2 are deleted
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.user1.shareKey'));
		$this->assertFalse($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.user2.shareKey'));

		// check if share keys for user3 and owner
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user3.shareKey'));
		// cleanup
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');

	}

	/**
	 * @medium
	 */
	function testDeleteFileKey() {

		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/existingFile.txt', 'data');

		// create folder structure for some dummy file key files
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1');

		// create dummy keyfile
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/dummyFile.txt.key', 'data');

		// recursive delete share keys from user1 and user2
		$result = Encryption\Keymanager::deleteFileKey($this->view, '/folder1/existingFile.txt');
		$this->assertFalse($result);

		$result2 = Encryption\Keymanager::deleteFileKey($this->view, '/folder1/dummyFile.txt');
		$this->assertTrue($result2);

		// check if file key from dummyFile was deleted
		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/dummyFile.txt.key'));

		// check if file key from existing file still exists
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/existingFile.txt.key'));

		// cleanup
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');

	}

	/**
	 * @medium
	 */
	function testDeleteFileKeyFolder() {

		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/existingFile.txt', 'data');

		// create folder structure for some dummy file key files
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1');

		// create dummy keyfile
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/dummyFile.txt.key', 'data');

		// recursive delete share keys from user1 and user2
		$result = Encryption\Keymanager::deleteFileKey($this->view, '/folder1');
		$this->assertFalse($result);

		// all file keys should still exists if we try to delete a folder with keys for which some files still exists
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/dummyFile.txt.key'));
		$this->assertTrue($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/existingFile.txt.key'));

		// delete folder
		$this->view->unlink('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		// create dummy keyfile
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1/dummyFile.txt.key', 'data');

		// now file keys should be deleted since the folder no longer exists
		$result = Encryption\Keymanager::deleteFileKey($this->view, '/folder1');
		$this->assertTrue($result);

		$this->assertFalse($this->view->file_exists(
			'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/keyfiles/folder1'));

		// cleanup
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');

	}

	function testDelAllShareKeysFile() {
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/existingFile.txt', 'data');

		// create folder structure for some dummy share key files
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1');

		// create some dummy share keys for the existing file
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user2.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user3.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey', 'data');

		// create some dummy share keys for a non-existing file
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user1.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user2.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user3.shareKey', 'data');
		$this->view->file_put_contents('/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey', 'data');

		// try to del all share keys from a existing file, should fail because the file still exists
		$result = Encryption\Keymanager::delAllShareKeys($this->view, Test_Encryption_Keymanager::TEST_USER, 'folder1/existingFile.txt');
		$this->assertFalse($result);

		// check if share keys still exists
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user1.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user2.shareKey'));
		$this->assertTrue($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/existingFile.txt.user3.shareKey'));

		// try to del all share keys froma file, should fail because the file still exists
		$result2 = Encryption\Keymanager::delAllShareKeys($this->view, Test_Encryption_Keymanager::TEST_USER, 'folder1/nonexistingFile.txt');
		$this->assertTrue($result2);

		// check if share keys are really gone
		$this->assertFalse($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.' . Test_Encryption_Keymanager::TEST_USER . '.shareKey'));
		$this->assertFalse($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user1.shareKey'));
		$this->assertFalse($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user2.shareKey'));
		$this->assertFalse($this->view->file_exists(
				'/'.Test_Encryption_Keymanager::TEST_USER.'/files_encryption/share-keys/folder1/nonexistingFile.txt.user3.shareKey'));

		// cleanup
		$this->view->deleteAll('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');

	}

	function testKeySetPreperation() {
		$basePath = '/'.Test_Encryption_Keymanager::TEST_USER.'/files';
		$path = '/folder1/subfolder/subsubfolder/file.txt';

		$this->assertFalse($this->view->is_dir($basePath . '/testKeySetPreperation'));

		$result = TestProtectedKeymanagerMethods::testKeySetPreperation($this->view, $path, $basePath);

		// return path without leading slash
		$this->assertSame('folder1/subfolder/subsubfolder/file.txt', $result);

		// check if directory structure was created
		$this->assertTrue($this->view->is_dir($basePath . '/folder1/subfolder/subsubfolder'));

		// cleanup
		$this->view->deleteAll($basePath . '/folder1');

	}
}

/**
 * dummy class to access protected methods of \OCA\Encryption\Keymanager for testing
 */
class TestProtectedKeymanagerMethods extends \OCA\Encryption\Keymanager {

	/**
	 * @param string $sharekey
	 */
	public static function testGetFilenameFromShareKey($sharekey) {
		return self::getFilenameFromShareKey($sharekey);
	}

	/**
	 * @param \OC\Files\View $view relative to data/
	 * @param string $path
	 * @param string $basePath
	 */
	public static function testKeySetPreperation($view, $path, $basePath) {
		return self::keySetPreparation($view, $path, $basePath);
	}
}
