<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Encryption\Tests\Command;

use OC\Files\View;
use OCA\Encryption\Command\FixEncryptedVersion;
use OCA\Encryption\Util;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\IUserManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Class FixEncryptedVersionTest
 *
 * @group DB
 * @package OCA\Encryption\Tests\Command
 */
class FixEncryptedVersionTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	private string $userId;

	private FixEncryptedVersion $fixEncryptedVersion;

	private CommandTester $commandTester;

	protected Util&MockObject $util;

	public function setUp(): void {
		parent::setUp();

		Server::get(IConfig::class)->setAppValue('encryption', 'useMasterKey', '1');

		$this->util = $this->getMockBuilder(Util::class)
			->disableOriginalConstructor()->getMock();

		$this->userId = $this->getUniqueId('user_');

		$this->createUser($this->userId, 'foo12345678');
		$tmpFolder = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->registerMount($this->userId, '\OC\Files\Storage\Local', '/' . $this->userId, ['datadir' => $tmpFolder]);
		$this->setupForUser($this->userId, 'foo12345678');
		$this->loginWithEncryption($this->userId);

		$this->fixEncryptedVersion = new FixEncryptedVersion(
			Server::get(IConfig::class),
			Server::get(LoggerInterface::class),
			Server::get(IRootFolder::class),
			Server::get(IUserManager::class),
			$this->util,
			new View('/')
		);
		$this->commandTester = new CommandTester($this->fixEncryptedVersion);

		$this->assertTrue(Server::get(\OCP\Encryption\IManager::class)->isEnabled());
		$this->assertTrue(Server::get(\OCP\Encryption\IManager::class)->isReady());
		$this->assertTrue(Server::get(\OCP\Encryption\IManager::class)->isReadyForUser($this->userId));
	}

	/**
	 * In this test the encrypted version of the file is less than the original value
	 * but greater than zero
	 */
	public function testEncryptedVersionLessThanOriginalValue(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

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
			'user' => $this->userId
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/foo.txt\"
The file \"/$this->userId/files/foo.txt\" is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/hello.txt\"
Attempting to fix the path: \"/$this->userId/files/hello.txt\"
Decrement the encrypted version to 1
Increment the encrypted version to 3
Increment the encrypted version to 4
Increment the encrypted version to 5
The file \"/$this->userId/files/hello.txt\" is: OK
Fixed the file: \"/$this->userId/files/hello.txt\" with version 5", $output);
		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/world.txt\"
Attempting to fix the path: \"/$this->userId/files/world.txt\"
Increment the encrypted version to 2
Increment the encrypted version to 3
Increment the encrypted version to 4
The file \"/$this->userId/files/world.txt\" is: OK
Fixed the file: \"/$this->userId/files/world.txt\" with version 4", $output);
	}

	/**
	 * In this test the encrypted version of the file is greater than the original value
	 * but greater than zero
	 */
	public function testEncryptedVersionGreaterThanOriginalValue(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

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
		$cacheInfo = ['encryptedVersion' => 5, 'encrypted' => 5];
		$cache1->put($fileCache1->getPath(), $cacheInfo);

		$fileInfo2 = $view->getFileInfo('world.txt');
		$storage2 = $fileInfo2->getStorage();
		$cache2 = $storage2->getCache();
		$filecache2 = $cache2->get($fileInfo2->getId());

		//Now change the encrypted version to 1
		$cacheInfo = ['encryptedVersion' => 6, 'encrypted' => 6];
		$cache2->put($filecache2->getPath(), $cacheInfo);

		$this->commandTester->execute([
			'user' => $this->userId
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/foo.txt\"
The file \"/$this->userId/files/foo.txt\" is: OK", $output);
		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/hello.txt\"
Attempting to fix the path: \"/$this->userId/files/hello.txt\"
Decrement the encrypted version to 4
Decrement the encrypted version to 3
The file \"/$this->userId/files/hello.txt\" is: OK
Fixed the file: \"/$this->userId/files/hello.txt\" with version 3", $output);
		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/world.txt\"
Attempting to fix the path: \"/$this->userId/files/world.txt\"
Decrement the encrypted version to 5
Decrement the encrypted version to 4
The file \"/$this->userId/files/world.txt\" is: OK
Fixed the file: \"/$this->userId/files/world.txt\" with version 4", $output);
	}

	public function testVersionIsRestoredToOriginalIfNoFixIsFound(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

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
			'user' => $this->userId
		]);

		$cacheInfo = $cache->get($fileInfo->getId());
		$encryptedVersion = $cacheInfo['encryptedVersion'];

		$this->assertEquals(15, $encryptedVersion);
	}

	public function testRepairUnencryptedFileWhenVersionIsSet(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

		// create a file, it's encrypted and also the version is set in the database
		$view->touch('hello.txt');

		$fileInfo1 = $view->getFileInfo('hello.txt');

		$storage1 = $fileInfo1->getStorage();
		$cache1 = $storage1->getCache();
		$fileCache1 = $cache1->get($fileInfo1->getId());

		// Now change the encrypted version
		$cacheInfo = ['encryptedVersion' => 1, 'encrypted' => 1];
		$cache1->put($fileCache1->getPath(), $cacheInfo);

		$absPath = $storage1->getSourcePath('') . $fileInfo1->getInternalPath();

		// create unencrypted file on disk, the version stays
		file_put_contents($absPath, 'hello contents');

		$this->commandTester->execute([
			'user' => $this->userId
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/hello.txt\"
Attempting to fix the path: \"/$this->userId/files/hello.txt\"
Set the encrypted version to 0 (unencrypted)
The file \"/$this->userId/files/hello.txt\" is: OK
Fixed the file: \"/$this->userId/files/hello.txt\" with version 0 (unencrypted)", $output);

		// the file can be decrypted
		$this->assertEquals('hello contents', $view->file_get_contents('hello.txt'));
	}

	/**
	 * Test commands with a file path
	 */
	public function testExecuteWithFilePathOption(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

		$view->touch('hello.txt');
		$view->touch('world.txt');

		$this->commandTester->execute([
			'user' => $this->userId,
			'--path' => '/hello.txt'
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/hello.txt\"
The file \"/$this->userId/files/hello.txt\" is: OK", $output);
		$this->assertStringNotContainsString('world.txt', $output);
	}

	/**
	 * Test commands with a directory path
	 */
	public function testExecuteWithDirectoryPathOption(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$view = new View('/' . $this->userId . '/files');

		$view->mkdir('sub');
		$view->touch('sub/hello.txt');
		$view->touch('world.txt');

		$this->commandTester->execute([
			'user' => $this->userId,
			'--path' => '/sub'
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString("Verifying the content of file \"/$this->userId/files/sub/hello.txt\"
The file \"/$this->userId/files/sub/hello.txt\" is: OK", $output);
		$this->assertStringNotContainsString('world.txt', $output);
	}

	public function testExecuteWithNoUser(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$this->commandTester->execute([
			'user' => null,
			'--path' => '/'
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString('Either a user id or --all needs to be provided', $output);
	}

	public function testExecuteWithBadUser(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$this->commandTester->execute([
			'user' => 'nonexisting',
			'--path' => '/'
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString('does not exist', $output);
	}

	/**
	 * Test commands with a directory path
	 */
	public function testExecuteWithNonExistentPath(): void {
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(true);

		$this->commandTester->execute([
			'user' => $this->userId,
			'--path' => '/non-exist'
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString('Please provide a valid path.', $output);
	}

	/**
	 * Test commands without master key
	 */
	public function testExecuteWithNoMasterKey(): void {
		Server::get(IConfig::class)->setAppValue('encryption', 'useMasterKey', '0');
		$this->util->expects($this->once())->method('isMasterKeyEnabled')
			->willReturn(false);

		$this->commandTester->execute([
			'user' => $this->userId,
		]);

		$output = $this->commandTester->getDisplay();

		$this->assertStringContainsString('only works with master key', $output);
	}
}
