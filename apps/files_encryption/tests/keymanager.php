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
	public $stateFilesTrashbin;
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

		\OC_User::setUserId(\Test_Encryption_Keymanager::TEST_USER);
		$this->userId = \Test_Encryption_Keymanager::TEST_USER;
		$this->pass = \Test_Encryption_Keymanager::TEST_USER;

		$userHome = \OC_User::getHome($this->userId);
		$this->dataDir = str_replace('/' . $this->userId, '', $userHome);

		// remember files_trashbin state
		$this->stateFilesTrashbin = OC_App::isEnabled('files_trashbin');

		// we don't want to tests with app files_trashbin enabled
		\OC_App::disable('files_trashbin');
	}

	function tearDown() {
		// reset app files_trashbin
		if ($this->stateFilesTrashbin) {
			OC_App::enable('files_trashbin');
		}
		else {
			OC_App::disable('files_trashbin');
		}
	}

	public static function tearDownAfterClass() {
		\OC_FileProxy::$enabled = true;

		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Keymanager::TEST_USER);
	}

	/**
	 * @medium
	 */
	function testGetPrivateKey() {

		$key = Encryption\Keymanager::getPrivateKey($this->view, $this->userId);

		$privateKey = Encryption\Crypt::symmetricDecryptFileContent($key, $this->pass);

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
	function testGetUserKeys() {

		$keys = Encryption\Keymanager::getUserKeys($this->view, $this->userId);

		$resPublic = openssl_pkey_get_public($keys['publicKey']);

		$this->assertTrue(is_resource($resPublic));

		$sslInfoPublic = openssl_pkey_get_details($resPublic);

		$this->assertArrayHasKey('key', $sslInfoPublic);

		$privateKey = Encryption\Crypt::symmetricDecryptFileContent($keys['privateKey'], $this->pass);

		$resPrivate = openssl_pkey_get_private($privateKey);

		$this->assertTrue(is_resource($resPrivate));

		$sslInfoPrivate = openssl_pkey_get_details($resPrivate);

		$this->assertArrayHasKey('key', $sslInfoPrivate);
	}

	/**
	 * @medium
	 */
	function testRecursiveDelShareKeys() {

		// generate filename
		$filename = '/tmp-' . uniqid() . '.txt';

		// create folder structure
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1');
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/subfolder');
		$this->view->mkdir('/'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/subfolder/subsubfolder');

		// enable encryption proxy
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = true;

		// save file with content
		$cryptedFile = file_put_contents('crypt:///'.Test_Encryption_Keymanager::TEST_USER.'/files/folder1/subfolder/subsubfolder' . $filename, $this->dataShort);

		// test that data was successfully written
		$this->assertTrue(is_int($cryptedFile));

		// change encryption proxy to previous state
		\OC_FileProxy::$enabled = $proxyStatus;

		// recursive delete keys
		Encryption\Keymanager::delShareKey($this->view, array('admin'), '/folder1/');

		// check if share key not exists
		$this->assertFalse($this->view->file_exists(
			'/admin/files_encryption/share-keys/folder1/subfolder/subsubfolder/' . $filename . '.admin.shareKey'));

		// enable encryption proxy
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = true;

		// cleanup
		$this->view->unlink('/admin/files/folder1');

		// change encryption proxy to previous state
		\OC_FileProxy::$enabled = $proxyStatus;
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
}
