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
require_once __DIR__ . '/../appinfo/app.php';

use OCA\Encryption;

/**
 * Class Test_Encryption_Util
 */
class Test_Encryption_Util extends \PHPUnit_Framework_TestCase {

	const TEST_ENCRYPTION_UTIL_USER1 = "test-util-user1";
	const TEST_ENCRYPTION_UTIL_LEGACY_USER = "test-legacy-user";

	public $userId;
	public $encryptionDir;
	public $publicKeyDir;
	public $pass;
	/**
	 * @var OC_FilesystemView
	 */
	public $view;
	public $keyfilesPath;
	public $publicKeyPath;
	public $privateKeyPath;
	/**
	 * @var \OCA\Encryption\Util
	 */
	public $util;
	public $dataShort;
	public $legacyEncryptedData;
	public $legacyEncryptedDataKey;
	public $legacyKey;
	public $stateFilesTrashbin;

	public static function setUpBeforeClass() {
		// reset backend
		\OC_User::clearBackends();
		\OC_User::useBackend('database');

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		// clear and register hooks
		\OC_FileProxy::clearProxies();
		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		// create test user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER, true);
	}


	function setUp() {
		\OC_User::setUserId(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1);
		$this->userId = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1;
		$this->pass = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1;

		// set content for encrypting / decrypting in tests
		$this->dataUrl = __DIR__ . '/../lib/crypt.php';
		$this->dataShort = 'hats';
		$this->dataLong = file_get_contents(__DIR__ . '/../lib/crypt.php');
		$this->legacyData = __DIR__ . '/legacy-text.txt';
		$this->legacyEncryptedData = __DIR__ . '/legacy-encrypted-text.txt';
		$this->legacyEncryptedDataKey = __DIR__ . '/encryption.key';
		$this->legacyKey = "30943623843030686906\0\0\0\0";

		$keypair = Encryption\Crypt::createKeypair();

		$this->genPublicKey = $keypair['publicKey'];
		$this->genPrivateKey = $keypair['privateKey'];

		$this->publicKeyDir = '/' . 'public-keys';
		$this->encryptionDir = '/' . $this->userId . '/' . 'files_encryption';
		$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
		$this->publicKeyPath =
			$this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
		$this->privateKeyPath =
			$this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key

		$this->view = new \OC_FilesystemView('/');

		$this->util = new Encryption\Util($this->view, $this->userId);

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
		// cleanup test user
		\OC_User::deleteUser(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1);
		\OC_User::deleteUser(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);
	}

	/**
	 * @medium
	 * @brief test that paths set during User construction are correct
	 */
	function testKeyPaths() {
		$util = new Encryption\Util($this->view, $this->userId);

		$this->assertEquals($this->publicKeyDir, $util->getPath('publicKeyDir'));
		$this->assertEquals($this->encryptionDir, $util->getPath('encryptionDir'));
		$this->assertEquals($this->keyfilesPath, $util->getPath('keyfilesPath'));
		$this->assertEquals($this->publicKeyPath, $util->getPath('publicKeyPath'));
		$this->assertEquals($this->privateKeyPath, $util->getPath('privateKeyPath'));

	}

	/**
	 * @medium
	 * @brief test setup of encryption directories
	 */
	function testSetupServerSide() {
		$this->assertEquals(true, $this->util->setupServerSide($this->pass));
	}

	/**
	 * @medium
	 * @brief test checking whether account is ready for encryption,
	 */
	function testUserIsReady() {
		$this->assertEquals(true, $this->util->ready());
	}

	/**
	 * @brief test checking whether account is not ready for encryption,
	 */
//	function testUserIsNotReady() {
//		$this->view->unlink($this->publicKeyDir);
//
//		$params['uid'] = $this->userId;
//		$params['password'] = $this->pass;
//		$this->assertFalse(OCA\Encryption\Hooks::login($params));
//
//		$this->view->unlink($this->privateKeyPath);
//	}

	/**
	 * @medium
	 * @brief test checking whether account is not ready for encryption,
	 */
	function testIsLegacyUser() {
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);

		$userView = new \OC_FilesystemView('/' . \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$encryptionKeyContent = file_get_contents($this->legacyEncryptedDataKey);
		$userView->file_put_contents('/encryption.key', $encryptionKeyContent);

		\OC_FileProxy::$enabled = $proxyStatus;

		$params['uid'] = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER;
		$params['password'] = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER;

		$this->setMigrationStatus(0, \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);

		$this->assertTrue(OCA\Encryption\Hooks::login($params));

		$this->assertEquals($this->legacyKey, \OC::$session->get('legacyKey'));
	}

	/**
	 * @medium
	 */
	function testRecoveryEnabledForUser() {

		$util = new Encryption\Util($this->view, $this->userId);

		// Record the value so we can return it to it's original state later
		$enabled = $util->recoveryEnabledForUser();

		$this->assertTrue($util->setRecoveryForUser(1));

		$this->assertEquals(1, $util->recoveryEnabledForUser());

		$this->assertTrue($util->setRecoveryForUser(0));

		$this->assertEquals(0, $util->recoveryEnabledForUser());

		// Return the setting to it's previous state
		$this->assertTrue($util->setRecoveryForUser($enabled));

	}

	/**
	 * @medium
	 */
	function testGetUidAndFilename() {

		\OC_User::setUserId(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1);

		$filename = '/tmp-' . time() . '.test';

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

		// Re-enable proxy - our work is done
		\OC_FileProxy::$enabled = $proxyStatus;

		$util = new Encryption\Util($this->view, $this->userId);

		list($fileOwnerUid, $file) = $util->getUidAndFilename($filename);

		$this->assertEquals(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1, $fileOwnerUid);

		$this->assertEquals($file, $filename);

		$this->view->unlink($this->userId . '/files/' . $filename);
	}

	/**
<	 * @brief Test that data that is read by the crypto stream wrapper
	 */
	function testGetFileSize() {
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1);

		$filename = 'tmp-' . time();
		$externalFilename = '/' . $this->userId . '/files/' . $filename;

		// Test for 0 byte files
		$problematicFileSizeData = "";
		$cryptedFile = $this->view->file_put_contents($externalFilename, $problematicFileSizeData);
		$this->assertTrue(is_int($cryptedFile));
		$this->assertEquals($this->util->getFileSize($externalFilename), 0);
		$decrypt = $this->view->file_get_contents($externalFilename);
		$this->assertEquals($problematicFileSizeData, $decrypt);
		$this->view->unlink($this->userId . '/files/' . $filename);

		// Test a file with 18377 bytes as in https://github.com/owncloud/mirall/issues/1009
		$problematicFileSizeData = str_pad("", 18377, "abc");
		$cryptedFile = $this->view->file_put_contents($externalFilename, $problematicFileSizeData);
		$this->assertTrue(is_int($cryptedFile));
		$this->assertEquals($this->util->getFileSize($externalFilename), 18377);
		$decrypt = $this->view->file_get_contents($externalFilename);
		$this->assertEquals($problematicFileSizeData, $decrypt);
		$this->view->unlink($this->userId . '/files/' . $filename);
	}

	/**
	 * @medium
	 */
	function testIsSharedPath() {
		$sharedPath = '/user1/files/Shared/test';
		$path = '/user1/files/test';

		$this->assertTrue($this->util->isSharedPath($sharedPath));

		$this->assertFalse($this->util->isSharedPath($path));
	}

	function testEncryptAll() {

		$filename = "/encryptAll" . time() . ".txt";
		$util = new Encryption\Util($this->view, $this->userId);

		// disable encryption to upload a unencrypted file
		\OC_App::disable('files_encryption');

		$this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

		$fileInfoUnencrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoUnencrypted));

		// enable file encryption again
		\OC_App::enable('files_encryption');

		// encrypt all unencrypted files
		$util->encryptAll('/' . $this->userId . '/' . 'files');

		$fileInfoEncrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoEncrypted));

		// check if mtime and etags unchanged
		$this->assertEquals($fileInfoEncrypted['mtime'], $fileInfoUnencrypted['mtime']);
		$this->assertEquals($fileInfoEncrypted['etag'], $fileInfoUnencrypted['etag']);

		$this->view->unlink($this->userId . '/files/' . $filename);
	}


	function testDecryptAll() {

		$filename = "/decryptAll" . time() . ".txt";
		$util = new Encryption\Util($this->view, $this->userId);

		$this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

		$fileInfoEncrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoEncrypted));

		// encrypt all unencrypted files
		$util->decryptAll('/' . $this->userId . '/' . 'files');

		$fileInfoUnencrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoUnencrypted));

		// check if mtime and etags unchanged
		$this->assertEquals($fileInfoEncrypted['mtime'], $fileInfoUnencrypted['mtime']);
		$this->assertEquals($fileInfoEncrypted['etag'], $fileInfoUnencrypted['etag']);

		$this->view->unlink($this->userId . '/files/' . $filename);

	}

	/**
	 * @large
	 */
	function testEncryptLegacyFiles() {
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);

		$userView = new \OC_FilesystemView('/' . \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);
		$view = new \OC_FilesystemView('/' . \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER . '/files');

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$encryptionKeyContent = file_get_contents($this->legacyEncryptedDataKey);
		$userView->file_put_contents('/encryption.key', $encryptionKeyContent);

		$legacyEncryptedData = file_get_contents($this->legacyEncryptedData);
		$view->mkdir('/test/');
		$view->mkdir('/test/subtest/');
		$view->file_put_contents('/test/subtest/legacy-encrypted-text.txt', $legacyEncryptedData);

		$fileInfo = $view->getFileInfo('/test/subtest/legacy-encrypted-text.txt');
		$fileInfo['encrypted'] = true;
		$view->putFileInfo('/test/subtest/legacy-encrypted-text.txt', $fileInfo);

		\OC_FileProxy::$enabled = $proxyStatus;

		$params['uid'] = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER;
		$params['password'] = \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER;

		$util = new Encryption\Util($this->view, \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);
		$this->setMigrationStatus(0, \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);

		$this->assertTrue(OCA\Encryption\Hooks::login($params));

		$this->assertEquals($this->legacyKey, \OC::$session->get('legacyKey'));

		$files = $util->findEncFiles('/' . \Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER . '/files/');

		$this->assertTrue(is_array($files));

		$found = false;
		foreach ($files['encrypted'] as $encryptedFile) {
			if ($encryptedFile['name'] === 'legacy-encrypted-text.txt') {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found);
	}

	/**
	 * @param $user
	 * @param bool $create
	 * @param bool $password
	 */
	public static function loginHelper($user, $create = false, $password = false) {
		if ($create) {
			\OC_User::createUser($user, $user);
		}

		if ($password === false) {
			$password = $user;
		}

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($user);
		\OC_User::setUserId($user);

		$params['uid'] = $user;
		$params['password'] = $password;
		OCA\Encryption\Hooks::login($params);
	}

	/**
	 * helper function to set migration status to the right value
	 * to be able to test the migration path
	 *
	 * @param $status needed migration status for test
	 * @param $user for which user the status should be set
	 * @return boolean
	 */
	private function setMigrationStatus($status, $user) {
		$sql = 'UPDATE `*PREFIX*encryption` SET `migration_status` = ? WHERE `uid` = ?';
		$args = array(
			$status,
			$user
		);

		$query = \OCP\DB::prepare($sql);
		if ($query->execute($args)) {
			return true;
		} else {
			return false;
		}
	}

}
