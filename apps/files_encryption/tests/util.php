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
	const TEST_ENCRYPTION_UTIL_USER2 = "test-util-user2";
	const TEST_ENCRYPTION_UTIL_GROUP1 = "test-util-group1";
	const TEST_ENCRYPTION_UTIL_GROUP2 = "test-util-group2";
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
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER2, true);
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER, true);

		// create groups
		\OC_Group::createGroup(self::TEST_ENCRYPTION_UTIL_GROUP1);
		\OC_Group::createGroup(self::TEST_ENCRYPTION_UTIL_GROUP2);

		// add user 1 to group1
		\OC_Group::addToGroup(self::TEST_ENCRYPTION_UTIL_USER1, self::TEST_ENCRYPTION_UTIL_GROUP1);
	}


	function setUp() {
		// login user
		\Test_Encryption_Util::loginHelper(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER1);
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
		\OC_User::deleteUser(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_USER2);
		\OC_User::deleteUser(\Test_Encryption_Util::TEST_ENCRYPTION_UTIL_LEGACY_USER);
		//cleanup groups
		\OC_Group::deleteGroup(self::TEST_ENCRYPTION_UTIL_GROUP1);
		\OC_Group::deleteGroup(self::TEST_ENCRYPTION_UTIL_GROUP2);
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
	 * @brief test detection of encrypted files
	 */
	function testIsEncryptedPath() {

		$util = new Encryption\Util($this->view, $this->userId);

		self::loginHelper($this->userId);

		$unencryptedFile = '/tmpUnencrypted-' . time() . '.txt';
		$encryptedFile =  '/tmpEncrypted-' . time() . '.txt';

		// Disable encryption proxy to write a unencrypted file
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$this->view->file_put_contents($this->userId . '/files/' . $unencryptedFile, $this->dataShort);

		// Re-enable proxy - our work is done
		\OC_FileProxy::$enabled = $proxyStatus;

		// write a encrypted file
		$this->view->file_put_contents($this->userId . '/files/' . $encryptedFile, $this->dataShort);

		// test if both files are detected correctly
		$this->assertFalse($util->isEncryptedPath($this->userId . '/files/' . $unencryptedFile));
		$this->assertTrue($util->isEncryptedPath($this->userId . '/files/' . $encryptedFile));

		// cleanup
		$this->view->unlink($this->userId . '/files/' . $unencryptedFile, $this->dataShort);
		$this->view->unlink($this->userId . '/files/' . $encryptedFile, $this->dataShort);

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

		$filename = '/tmp-' . uniqid() . '.test';

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

		$filename = 'tmp-' . uniqid();
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

		$filename = "/encryptAll" . uniqid() . ".txt";
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

		$filename = "/decryptAll" . uniqid() . ".txt";
		$util = new Encryption\Util($this->view, $this->userId);

		$this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

		$fileInfoEncrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoEncrypted));
		$this->assertEquals($fileInfoEncrypted['encrypted'], 1);

		// decrypt all encrypted files
		$result = $util->decryptAll('/' . $this->userId . '/' . 'files');

		$this->assertTrue($result);

		$fileInfoUnencrypted = $this->view->getFileInfo($this->userId . '/files/' . $filename);

		$this->assertTrue(is_array($fileInfoUnencrypted));

		// check if mtime and etags unchanged
		$this->assertEquals($fileInfoEncrypted['mtime'], $fileInfoUnencrypted['mtime']);
		$this->assertEquals($fileInfoEncrypted['etag'], $fileInfoUnencrypted['etag']);
		// file should no longer be encrypted
		$this->assertEquals(0, $fileInfoUnencrypted['encrypted']);

		$this->view->unlink($this->userId . '/files/' . $filename);

	}

	/**
	 * test if all keys get moved to the backup folder correctly
	 */
	function testBackupAllKeys() {
		self::loginHelper(self::TEST_ENCRYPTION_UTIL_USER1);

		// create some dummy key files
		$encPath = '/' . self::TEST_ENCRYPTION_UTIL_USER1 . '/files_encryption';
		$this->view->file_put_contents($encPath . '/keyfiles/foo.key', 'key');
		$this->view->file_put_contents($encPath . '/share-keys/foo.user1.shareKey', 'share key');
		$this->view->mkdir($encPath . '/keyfiles/subfolder/');
		$this->view->mkdir($encPath . '/share-keys/subfolder/');
		$this->view->file_put_contents($encPath . '/keyfiles/subfolder/foo.key', 'key');
		$this->view->file_put_contents($encPath . '/share-keys/subfolder/foo.user1.shareKey', 'share key');


		$util = new \OCA\Encryption\Util($this->view, self::TEST_ENCRYPTION_UTIL_USER1);

		$util->backupAllKeys('testing');

		$encFolderContent = $this->view->getDirectoryContent($encPath);

		$backupPath = '';
		foreach ($encFolderContent as $c) {
			$name = $c['name'];
			if (substr($name, 0, strlen('backup'))  === 'backup') {
				$backupPath = $encPath . '/'. $c['name'];
				break;
			}
		}

		$this->assertTrue($backupPath !== '');

		// check backupDir Content
		$this->assertTrue($this->view->is_dir($backupPath . '/keyfiles'));
		$this->assertTrue($this->view->is_dir($backupPath . '/share-keys'));
		$this->assertTrue($this->view->file_exists($backupPath . '/keyfiles/foo.key'));
		$this->assertTrue($this->view->file_exists($backupPath . '/share-keys/foo.user1.shareKey'));
		$this->assertTrue($this->view->file_exists($backupPath . '/keyfiles/subfolder/foo.key'));
		$this->assertTrue($this->view->file_exists($backupPath . '/share-keys/subfolder/foo.user1.shareKey'));
		$this->assertTrue($this->view->file_exists($backupPath . '/' . self::TEST_ENCRYPTION_UTIL_USER1 . '.private.key'));
		$this->assertTrue($this->view->file_exists($backupPath . '/' . self::TEST_ENCRYPTION_UTIL_USER1 . '.public.key'));

		//cleanup
		$this->view->deleteAll($backupPath);
		$this->view->unlink($encPath . '/keyfiles/foo.key', 'key');
		$this->view->unlink($encPath . '/share-keys/foo.user1.shareKey', 'share key');
	}


	function testDescryptAllWithBrokenFiles() {

		$file1 = "/decryptAll1" . uniqid() . ".txt";
		$file2 = "/decryptAll2" . uniqid() . ".txt";

		$util = new Encryption\Util($this->view, $this->userId);

		$this->view->file_put_contents($this->userId . '/files/' . $file1, $this->dataShort);
		$this->view->file_put_contents($this->userId . '/files/' . $file2, $this->dataShort);

		$fileInfoEncrypted1 = $this->view->getFileInfo($this->userId . '/files/' . $file1);
		$fileInfoEncrypted2 = $this->view->getFileInfo($this->userId . '/files/' . $file2);

		$this->assertTrue(is_array($fileInfoEncrypted1));
		$this->assertTrue(is_array($fileInfoEncrypted2));
		$this->assertEquals($fileInfoEncrypted1['encrypted'], 1);
		$this->assertEquals($fileInfoEncrypted2['encrypted'], 1);

		// rename keyfile for file1 so that the decryption for file1 fails
		// Expected behaviour: decryptAll() returns false, file2 gets decrypted anyway
		$this->view->rename($this->userId . '/files_encryption/keyfiles/' . $file1 . '.key',
				$this->userId . '/files_encryption/keyfiles/' . $file1 . '.key.moved');

		// decrypt all encrypted files
		$result = $util->decryptAll('/' . $this->userId . '/' . 'files');

		$this->assertFalse($result);

		$fileInfoUnencrypted1 = $this->view->getFileInfo($this->userId . '/files/' . $file1);
		$fileInfoUnencrypted2 = $this->view->getFileInfo($this->userId . '/files/' . $file2);

		$this->assertTrue(is_array($fileInfoUnencrypted1));
		$this->assertTrue(is_array($fileInfoUnencrypted2));

		// file1 should be still encrypted; file2 should be decrypted
		$this->assertEquals(1, $fileInfoUnencrypted1['encrypted']);
		$this->assertEquals(0, $fileInfoUnencrypted2['encrypted']);

		// keyfiles and share keys should still exist
		$this->assertTrue($this->view->is_dir($this->userId . '/files_encryption/keyfiles/'));
		$this->assertTrue($this->view->is_dir($this->userId . '/files_encryption/share-keys/'));

		// rename the keyfile for file1 back
		$this->view->rename($this->userId . '/files_encryption/keyfiles/' . $file1 . '.key.moved',
				$this->userId . '/files_encryption/keyfiles/' . $file1 . '.key');

		// try again to decrypt all encrypted files
		$result = $util->decryptAll('/' . $this->userId . '/' . 'files');

		$this->assertTrue($result);

		$fileInfoUnencrypted1 = $this->view->getFileInfo($this->userId . '/files/' . $file1);
		$fileInfoUnencrypted2 = $this->view->getFileInfo($this->userId . '/files/' . $file2);

		$this->assertTrue(is_array($fileInfoUnencrypted1));
		$this->assertTrue(is_array($fileInfoUnencrypted2));

		// now both files should be decrypted
		$this->assertEquals(0, $fileInfoUnencrypted1['encrypted']);
		$this->assertEquals(0, $fileInfoUnencrypted2['encrypted']);

		// keyfiles and share keys should be deleted
		$this->assertFalse($this->view->is_dir($this->userId . '/files_encryption/keyfiles/'));
		$this->assertFalse($this->view->is_dir($this->userId . '/files_encryption/share-keys/'));

		$this->view->unlink($this->userId . '/files/' . $file1);
		$this->view->unlink($this->userId . '/files/' . $file2);

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
	 * @dataProvider dataProviderFortestIsMountPointApplicableToUser
	 */
	function testIsMountPointApplicableToUser($mount, $expectedResult) {
		self::loginHelper(self::TEST_ENCRYPTION_UTIL_USER1);
		$dummyClass = new DummyUtilClass($this->view, self::TEST_ENCRYPTION_UTIL_USER1);
		$result = $dummyClass->testIsMountPointApplicableToUser($mount);

		$this->assertSame($expectedResult, $result);
	}

	function dataProviderFortestIsMountPointApplicableToUser() {
		return array(
			array(array('applicable' => array('groups' => array(), 'users' => array(self::TEST_ENCRYPTION_UTIL_USER1))), true),
			array(array('applicable' => array('groups' => array(), 'users' => array(self::TEST_ENCRYPTION_UTIL_USER2))), false),
			array(array('applicable' => array('groups' => array(self::TEST_ENCRYPTION_UTIL_GROUP1), 'users' => array())), true),
			array(array('applicable' => array('groups' => array(self::TEST_ENCRYPTION_UTIL_GROUP1), 'users' => array(self::TEST_ENCRYPTION_UTIL_USER2))), true),
			array(array('applicable' => array('groups' => array(self::TEST_ENCRYPTION_UTIL_GROUP2), 'users' => array(self::TEST_ENCRYPTION_UTIL_USER2))), false),
			array(array('applicable' => array('groups' => array(self::TEST_ENCRYPTION_UTIL_GROUP2), 'users' => array(self::TEST_ENCRYPTION_UTIL_USER2, 'all'))), true),
			array(array('applicable' => array('groups' => array(self::TEST_ENCRYPTION_UTIL_GROUP2), 'users' => array('all'))), true),
		);
	}

	/**
	 * @param string $user
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

	public static function logoutHelper() {
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
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

/**
 * dummy class extends  \OCA\Encryption\Util to access protected methods for testing
 */
class DummyUtilClass extends \OCA\Encryption\Util {
	public function testIsMountPointApplicableToUser($mount) {
		return $this->isMountPointApplicableToUser($mount);
	}
}
