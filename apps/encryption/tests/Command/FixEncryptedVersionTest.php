<?php
/**
 * @author Sujith Haridasan <sharidasan@owncloud.com>
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\Encryption\Tests\Command;

use OC\Files\Filesystem;
use OC\Files\View;
use OCA\Encryption\Command\FixEncryptedVersion;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCA\Encryption\Util;
use OCP\Files\IRootFolder;
use OCP\IUserManager;
use Symfony\Component\Console\Tester\CommandTester;
use OCA\Encryption\Users\Setup;
use Test\TestCase;

/**
 * Class FixEncryptedVersionTest
 *
 * @group DB
 * @package OCA\Encryption\Tests\Command
 */
class FixEncryptedVersionTest extends TestCase {
	public const TEST_ENCRYPTION_VERSION_AFFECTED_USER = 'test_enc_version_affected_user1';

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IUserManager */
	private $userManager;

	/** @var View */
	private $view;

	/** @var FixEncryptedVersion */
	private $fixEncryptedVersion;

	/** @var CommandTester */
	private $commandTester;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		//Enable encryption
		\OC::$server->getConfig()->setAppValue('core', 'encryption_enabled', 'yes');
		//Enable Masterkey
		\OC::$server->getConfig()->setAppValue('encryption', 'useMasterKey', '1');
		$crypt = new Crypt(\OC::$server->getLogger(), \OC::$server->getUserSession(), \OC::$server->getConfig(), \OC::$server->getL10N('encryption'));
		$encryptionSession = new Session(\OC::$server->getSession());
		$view = new View("/");
		$encryptionUtil = new Util($view, $crypt, \OC::$server->getLogger(), \OC::$server->getUserSession(), \OC::$server->getConfig(), \OC::$server->getUserManager());
		$keyManager = new KeyManager(
			\OC::$server->getEncryptionKeyStorage(),
			$crypt,
			\OC::$server->getConfig(),
			\OC::$server->getUserSession(),
			$encryptionSession,
			\OC::$server->getLogger(),
			$encryptionUtil
		);
		$userSetup = new Setup(\OC::$server->getLogger(), \OC::$server->getUserSession(), $crypt, $keyManager);
		$userSetup->setupSystem();
		\OC::$server->getUserManager()->createUser(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		\OC\Files\Filesystem::clearMounts();
		$user = \OC::$server->getUserManager()->get(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER);
		if ($user !== null) {
			$user->delete();
		}
		\OC::$server->getConfig()->deleteAppValue('core', 'encryption_enabled');
		\OC::$server->getConfig()->deleteAppValue('core', 'default_encryption_module');
		\OC::$server->getConfig()->deleteAppValues('encryption');
		Filesystem::getLoader()->removeStorageWrapper("oc_encryption");
	}

	public function setUp(): void {
		parent::setUp();
		$this->rootFolder = \OC::$server->getRootFolder();
		$this->userManager = \OC::$server->getUserManager();
		$this->view = new View("/");
		$this->fixEncryptedVersion = new FixEncryptedVersion($this->rootFolder, $this->userManager, $this->view);
		$this->commandTester = new CommandTester($this->fixEncryptedVersion);
	}

	/**
	 * In this test the encrypted version is set to zero whereas it should have been
	 * set to a positive non zero number.
	 */
	public function testEncryptedVersionIsNotZero() {
		\OC::$server->getUserSession()->login(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');
		$view = new View("/" . self::TEST_ENCRYPTION_VERSION_AFFECTED_USER . "/files");

		$view->touch('hello.txt');
		$view->touch('world.txt');
		$view->file_put_contents('hello.txt', 'a test string for hello');
		$view->file_put_contents('world.txt', 'a test string for world');

		$fileInfo = $view->getFileInfo('hello.txt');

		$storage = $fileInfo->getStorage();
		$cache = $storage->getCache();
		$fileCache = $cache->get($fileInfo->getId());

		//Now change the encrypted version to zero
		$cacheInfo = ['encryptedVersion' => 0, 'encrypted' => 0];
		$cache->put($fileCache->getPath(), $cacheInfo);

		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/hello.txt
Attempting to fix the path: /test_enc_version_affected_user1/files/hello.txt
Increment the encrypted version to 1
The file /test_enc_version_affected_user1/files/hello.txt is: OK
Fixed the file: /test_enc_version_affected_user1/files/hello.txt with version 1", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/world.txt
The file /test_enc_version_affected_user1/files/world.txt is: OK", $output);
	}

	/**
	 * In this test the encrypted version of the file is less than the original value
	 * but greater than zero
	 */
	public function testEncryptedVersionLessThanOriginalValue() {
		\OC::$server->getUserSession()->login(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');
		$view = new View("/" . self::TEST_ENCRYPTION_VERSION_AFFECTED_USER . "/files");

		$view->touch('hello.txt');
		$view->touch('world.txt');
		$view->touch('foo.txt');
		$view->file_put_contents('hello.txt', 'a test string for hello');
		$view->file_put_contents('hello.txt', 'Yet another value');
		$view->file_put_contents('hello.txt', 'Lets modify again1');
		$view->file_put_contents('hello.txt', 'Lets modify again2');
		$view->file_put_contents('hello.txt', 'Lets modify again3');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('foo.txt', 'a foo test');

		$fileInfo1 = $view->getFileInfo('hello.txt');

		$storage1 = $fileInfo1->getStorage();
		$cache1 = $storage1->getCache();
		$fileCache1 = $cache1->get($fileInfo1->getId());

		//Now change the encrypted version to two
		$cacheInfo = ['encryptedVersion' => 2, 'encrypted' => 2];
		$cache1->put($fileCache1->getPath(), $cacheInfo);

		$fileInfo2 = $view->getFileInfo('world.txt');
		$storage2 = $fileInfo2->getStorage();
		$cache2 = $storage2->getCache();
		$filecache2 = $cache2->get($fileInfo2->getId());

		//Now change the encrypted version to 1
		$cacheInfo = ['encryptedVersion' => 1, 'encrypted' => 1];
		$cache2->put($filecache2->getPath(), $cacheInfo);

		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/foo.txt
The file /test_enc_version_affected_user1/files/foo.txt is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/hello.txt
Attempting to fix the path: /test_enc_version_affected_user1/files/hello.txt
Decrement the encrypted version to 1
Increment the encrypted version to 3
Increment the encrypted version to 4
Increment the encrypted version to 5
Increment the encrypted version to 6
The file /test_enc_version_affected_user1/files/hello.txt is: OK
Fixed the file: /test_enc_version_affected_user1/files/hello.txt with version 6", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/world.txt
Attempting to fix the path: /test_enc_version_affected_user1/files/world.txt
Increment the encrypted version to 2
Increment the encrypted version to 3
Increment the encrypted version to 4
Increment the encrypted version to 5
The file /test_enc_version_affected_user1/files/world.txt is: OK
Fixed the file: /test_enc_version_affected_user1/files/world.txt with version 5", $output);
	}

	/**
	 * In this test the encrypted version of the file is greater than the original value
	 * but greater than zero
	 */
	public function testEncryptedVersionGreaterThanOriginalValue() {
		\OC::$server->getUserSession()->login(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');
		$view = new View("/" . self::TEST_ENCRYPTION_VERSION_AFFECTED_USER . "/files");

		$view->touch('hello.txt');
		$view->touch('world.txt');
		$view->touch('foo.txt');
		$view->file_put_contents('hello.txt', 'a test string for hello');
		$view->file_put_contents('hello.txt', 'Lets modify again2');
		$view->file_put_contents('hello.txt', 'Lets modify again3');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('world.txt', 'a test string for world');
		$view->file_put_contents('foo.txt', 'a foo test');

		$fileInfo1 = $view->getFileInfo('hello.txt');

		$storage1 = $fileInfo1->getStorage();
		$cache1 = $storage1->getCache();
		$fileCache1 = $cache1->get($fileInfo1->getId());

		//Now change the encrypted version to fifteen
		$cacheInfo = ['encryptedVersion' => 15, 'encrypted' => 15];
		$cache1->put($fileCache1->getPath(), $cacheInfo);

		$fileInfo2 = $view->getFileInfo('world.txt');
		$storage2 = $fileInfo2->getStorage();
		$cache2 = $storage2->getCache();
		$filecache2 = $cache2->get($fileInfo2->getId());

		//Now change the encrypted version to 1
		$cacheInfo = ['encryptedVersion' => 15, 'encrypted' => 15];
		$cache2->put($filecache2->getPath(), $cacheInfo);

		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/foo.txt
The file /test_enc_version_affected_user1/files/foo.txt is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/hello.txt
Attempting to fix the path: /test_enc_version_affected_user1/files/hello.txt
Decrement the encrypted version to 14
Decrement the encrypted version to 13
Decrement the encrypted version to 12
Decrement the encrypted version to 11
Decrement the encrypted version to 10
Decrement the encrypted version to 9
The file /test_enc_version_affected_user1/files/hello.txt is: OK
Fixed the file: /test_enc_version_affected_user1/files/hello.txt with version 9", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/world.txt
Attempting to fix the path: /test_enc_version_affected_user1/files/world.txt
Decrement the encrypted version to 14
Decrement the encrypted version to 13
Decrement the encrypted version to 12
Decrement the encrypted version to 11
Decrement the encrypted version to 10
Decrement the encrypted version to 9
The file /test_enc_version_affected_user1/files/world.txt is: OK
Fixed the file: /test_enc_version_affected_user1/files/world.txt with version 9", $output);
	}

	public function testVersionIsRestoredToOriginalIfNoFixIsFound() {
		\OC::$server->getUserSession()->login(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');
		$view = new View("/" . self::TEST_ENCRYPTION_VERSION_AFFECTED_USER . "/files");

		$view->touch('bar.txt');
		for ($i = 0; $i < 40; $i++) {
			$view->file_put_contents('bar.txt', 'a test string for hello ' . $i);
		}

		$fileInfo = $view->getFileInfo('bar.txt');

		$storage = $fileInfo->getStorage();
		$cache = $storage->getCache();
		$fileCache = $cache->get($fileInfo->getId());

		$cacheInfo = ['encryptedVersion' => 15, 'encrypted' => 15];
		$cache->put($fileCache->getPath(), $cacheInfo);

		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER
		]);

		$cacheInfo = $cache->get($fileInfo->getId());
		$encryptedVersion = $cacheInfo["encryptedVersion"];

		$this->assertEquals(15, $encryptedVersion);
	}

	/**
	 * Test commands with a file path
	 */
	public function testExecuteWithFilePathOption() {
		\OC::$server->getUserSession()->login(self::TEST_ENCRYPTION_VERSION_AFFECTED_USER, 'foo');

		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER,
			'--path' => "/hello.txt"
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/hello.txt
The file /test_enc_version_affected_user1/files/hello.txt is: OK", $output);
	}

	/**
	 * Test commands with a directory path
	 */
	public function testExecuteWithDirectoryPathOption() {
		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER,
			'--path' => "/"
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/hello.txt
The file /test_enc_version_affected_user1/files/hello.txt is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/world.txt
The file /test_enc_version_affected_user1/files/world.txt is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file /test_enc_version_affected_user1/files/foo.txt
The file /test_enc_version_affected_user1/files/foo.txt is: OK", $output);
	}

	/**
	 * Test commands with a directory path
	 */
	public function testExecuteWithNoUser() {
		$this->commandTester->execute([
			'user' => null,
			'--path' => "/"
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("No user id provided.", $output);
	}

	/**
	 * Test commands with a directory path
	 */
	public function testExecuteWithNonExistentPath() {
		$this->commandTester->execute([
			'user' => self::TEST_ENCRYPTION_VERSION_AFFECTED_USER,
			'--path' => "/non-exist"
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Please provide a valid path.", $output);
	}
}
