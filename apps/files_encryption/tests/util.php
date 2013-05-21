<?php
/**
 * Copyright (c) 2012 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once realpath(dirname(__FILE__) . '/../../../lib/base.php');
require_once realpath(dirname(__FILE__) . '/../lib/crypt.php');
require_once realpath(dirname(__FILE__) . '/../lib/keymanager.php');
require_once realpath(dirname(__FILE__) . '/../lib/proxy.php');
require_once realpath(dirname(__FILE__) . '/../lib/stream.php');
require_once realpath(dirname(__FILE__) . '/../lib/util.php');
require_once realpath(dirname(__FILE__) . '/../appinfo/app.php');

use OCA\Encryption;

/**
 * Class Test_Encryption_Util
 */
class Test_Encryption_Util extends \PHPUnit_Framework_TestCase
{

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
	public $lagacyKey;

	function setUp()
	{
		// reset backend
		\OC_User::useBackend('database');

		\OC_User::setUserId('admin');
		$this->userId = 'admin';
		$this->pass = 'admin';

		// set content for encrypting / decrypting in tests
		$this->dataUrl = realpath(dirname(__FILE__) . '/../lib/crypt.php');
		$this->dataShort = 'hats';
		$this->dataLong = file_get_contents(realpath(dirname(__FILE__) . '/../lib/crypt.php'));
		$this->legacyData = realpath(dirname(__FILE__) . '/legacy-text.txt');
		$this->legacyEncryptedData = realpath(dirname(__FILE__) . '/legacy-encrypted-text.txt');
		$this->legacyEncryptedDataKey = realpath(dirname(__FILE__) . '/encryption.key');
		$this->lagacyKey = '62829813025828180801';

		$keypair = Encryption\Crypt::createKeypair();

		$this->genPublicKey = $keypair['publicKey'];
		$this->genPrivateKey = $keypair['privateKey'];

		$this->publicKeyDir = '/' . 'public-keys';
		$this->encryptionDir = '/' . $this->userId . '/' . 'files_encryption';
		$this->keyfilesPath = $this->encryptionDir . '/' . 'keyfiles';
		$this->publicKeyPath = $this->publicKeyDir . '/' . $this->userId . '.public.key'; // e.g. data/public-keys/admin.public.key
		$this->privateKeyPath = $this->encryptionDir . '/' . $this->userId . '.private.key'; // e.g. data/admin/admin.private.key

		$this->view = new \OC_FilesystemView('/');

		$userHome = \OC_User::getHome($this->userId);
		$this->dataDir = str_replace('/' . $this->userId, '', $userHome);

		// Filesystem related hooks
		\OCA\Encryption\Helper::registerFilesystemHooks();

		\OC_FileProxy::register(new OCA\Encryption\Proxy());

		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($this->userId);
		\OC_User::setUserId($this->userId);

		$params['uid'] = $this->userId;
		$params['password'] = $this->pass;
		OCA\Encryption\Hooks::login($params);

		$this->util = new Encryption\Util($this->view, $this->userId);
	}

	function tearDown()
	{

		\OC_FileProxy::clearProxies();
	}

	/**
	 * @brief test that paths set during User construction are correct
	 */
	function testKeyPaths()
	{
		$util = new Encryption\Util($this->view, $this->userId);

		$this->assertEquals($this->publicKeyDir, $util->getPath('publicKeyDir'));
		$this->assertEquals($this->encryptionDir, $util->getPath('encryptionDir'));
		$this->assertEquals($this->keyfilesPath, $util->getPath('keyfilesPath'));
		$this->assertEquals($this->publicKeyPath, $util->getPath('publicKeyPath'));
		$this->assertEquals($this->privateKeyPath, $util->getPath('privateKeyPath'));

	}

	/**
	 * @brief test setup of encryption directories
	 */
	function testSetupServerSide()
	{
		$this->assertEquals(true, $this->util->setupServerSide($this->pass));
	}

	/**
	 * @brief test checking whether account is ready for encryption,
	 */
	function testUserIsReady()
	{
		$this->assertEquals(true, $this->util->ready());
	}

	/**
	 * @brief test checking whether account is not ready for encryption,
	 */
	function testUserIsNotReady()
	{
		$this->view->unlink($this->publicKeyDir);

		$params['uid'] = $this->userId;
		$params['password'] = $this->pass;
		$this->assertFalse(OCA\Encryption\Hooks::login($params));

		$this->view->unlink($this->privateKeyPath);
	}

	/**
	 * @brief test checking whether account is not ready for encryption,
	 */
	function testIsLagacyUser()
	{
		$userView = new \OC_FilesystemView( '/' . $this->userId );

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$encryptionKeyContent = file_get_contents($this->legacyEncryptedDataKey);
		$userView->file_put_contents('/encryption.key', $encryptionKeyContent);

		\OC_FileProxy::$enabled = $proxyStatus;

		$params['uid'] = $this->userId;
		$params['password'] = $this->pass;

		$util = new Encryption\Util($this->view, $this->userId);
		$util->setMigrationStatus(0);

		$this->assertTrue(OCA\Encryption\Hooks::login($params));

		$this->assertEquals($this->lagacyKey, $_SESSION['legacyKey']);
	}

	function testRecoveryEnabledForUser()
	{

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

	function testGetUidAndFilename()
	{

		\OC_User::setUserId('admin');

		$filename = 'tmp-' . time() . '.test';

		// Disable encryption proxy to prevent recursive calls
		$proxyStatus = \OC_FileProxy::$enabled;
		\OC_FileProxy::$enabled = false;

		$this->view->file_put_contents($this->userId . '/files/' . $filename, $this->dataShort);

		// Re-enable proxy - our work is done
		\OC_FileProxy::$enabled = $proxyStatus;

		$util = new Encryption\Util($this->view, $this->userId);

		list($fileOwnerUid, $file) = $util->getUidAndFilename($filename);

		$this->assertEquals('admin', $fileOwnerUid);

		$this->assertEquals($file, $filename);
	}

	function testIsSharedPath() {
		$sharedPath = '/user1/files/Shared/test';
		$path = '/user1/files/test';

		$this->assertTrue($this->util->isSharedPath($sharedPath));

		$this->assertFalse($this->util->isSharedPath($path));
	}

	function testEncryptLagacyFiles()
	{
		$userView = new \OC_FilesystemView( '/' . $this->userId);
		$view = new \OC_FilesystemView( '/' . $this->userId . '/files' );

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

		$params['uid'] = $this->userId;
		$params['password'] = $this->pass;

		$util = new Encryption\Util($this->view, $this->userId);
		$util->setMigrationStatus(0);

		$this->assertTrue(OCA\Encryption\Hooks::login($params));

		$this->assertEquals($this->lagacyKey, $_SESSION['legacyKey']);

		$files = $util->findEncFiles('/' . $this->userId . '/files/');

		$this->assertTrue(is_array($files));

		$found = false;
		foreach($files['encrypted'] as $encryptedFile) {
			if($encryptedFile['name'] === 'legacy-encrypted-text.txt') {
				$found = true;
				break;
			}
		}

		$this->assertTrue($found);
	}
}