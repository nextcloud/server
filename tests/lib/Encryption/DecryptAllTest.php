<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\DecryptAll;
use OC\Encryption\Manager;
use OC\Files\FileInfo;
use OC\Files\View;
use OCP\Files\Storage\IStorage;
use OCP\IUserManager;
use OCP\UserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Unit tests for DecryptAll orchestration logic in lib/private/Encryption.
 *
 * This class covers high-level user and file decryption workflows as exposed by the main encryption service,
 * differentiating it from tests in app-layer or crypto-layer classes that cover low-level session, key, or CLI logic.
 */
class DecryptAllTest extends TestCase {
	private const TEST_USER = 'user1';
	private const TEST_FILE = 'test.txt';
	
	private IUserManager&MockObject $mockUserManager;
	private Manager&MockObject $mockEncryptionManager;
	private View&MockObject $mockView;
	private InputInterface&MockObject $mockInputInterface;
	private OutputInterface&MockObject $mockOutputInterface;
	private UserInterface&MockObject $mockUserInterface;

	private DecryptAll $instance;

	protected function setUp(): void {
		parent::setUp();
		$this->initializeMocks();
		$this->instance = new DecryptAll(
			$this->mockEncryptionManager,
			$this->mockUserManager,
			$this->mockView
		);
	}

	private function initializeMocks(): void {
		$this->mockUserManager = $this->createMock(IUserManager::class);
		$this->mockEncryptionManager = $this->createMock(Manager::class);
		$this->mockView = $this->createMock(View::class);
		$this->mockInputInterface = $this->createMock(InputInterface::class);
		$this->mockOutputInterface = $this->createMock(OutputInterface::class);
		$this->mockOutputInterface->method('isDecorated')->willReturn(false);
		$this->mockUserInterface = $this->createMock(UserInterface::class);
	}

	/**
	 * Covers: scenarios where user does/does not exist when running decryptAll.
	 * Ensures expected messaging and correct function return.
	 */
	#[DataProvider('provideUserExistence')]
	public function testDecryptAllWithNonexistentUser(bool $userExists): void {
		$this->mockUserManager->method('userExists')->willReturn($userExists);

		if (!$userExists) {
			$this->mockOutputInterface->expects($this->once())->method('writeln')
				->with(sprintf('User "%s" does not exist. Please check the username and try again', self::TEST_USER));
		}

		$result = $this->instance->decryptAll($this->mockInputInterface, $this->mockOutputInterface, self::TEST_USER);

		$this->assertSame($userExists, $result);
	}

	public static function provideUserExistence(): array {
		return [
			 [true],	// userExists
			 [false],	// !userExists
		];
	}
	
	/**
	 * Covers: encrypted vs. non-encrypted file decryption path and side effects.
	 * Ensures expected filesystem operations happen only when appropriate.
	 */
	#[DataProvider('provideDecryptionStatus')]
	public function testDecryptFileBehavior(bool $isEncrypted): void {
		// Configure fileInfo for encrypted/non-encrypted
		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->method('isEncrypted')->willReturn($isEncrypted);
		$this->mockView->method('getFileInfo')->willReturn($fileInfo);

		// Set up mock for the timestamp function
		$instance = $this->getMockBuilder(DecryptAll::class)
			->setConstructorArgs([$this->mockEncryptionManager, $this->mockUserManager, $this->mockView])
			->onlyMethods(['getTimestamp'])
			->getMock();

		if ($isEncrypted) {
			// Only when encrypted, "copy" and "rename" should be called
			$instance->method('getTimestamp')->willReturn(42);

			$this->mockView->expects($this->once())
				->method('copy')
				->with(self::TEST_FILE, self::TEST_FILE . '.decrypted.42');
			$this->mockView->expects($this->once())
				->method('rename')
				->with(self::TEST_FILE . '.decrypted.42', self::TEST_FILE);
		} else {
			// If not encrypted, these operations should not happen
			$instance->expects($this->never())->method('getTimestamp');
			$this->mockView->expects($this->never())->method('copy');
			$this->mockView->expects($this->never())->method('rename');
		}

		$result = $instance->decryptFile(self::TEST_FILE);

		$this->assertTrue($result);
	}

	public static function provideDecryptionStatus(): array {
		return [
			[true],	// isEncrypted
			[false]	// !isEncrypted
		];
	}

	/**
	 * Covers: workflow for successful module preparation and user file decryption
	 * Ensures the main orchestration calls collaborators as expected.
	 */
	public function testDecryptAllTriggersModuleAndUserWorkflows(): void {
		$this->mockUserManager->method('userExists')->willReturn(true);

		// Partial mock to verify internal orchestration logic
		$instance = $this->getMockBuilder(DecryptAll::class)
			->setConstructorArgs([
				$this->mockEncryptionManager,
				$this->mockUserManager,
				$this->mockView
			])
			->onlyMethods(['prepareEncryptionModules', 'decryptAllUsersFiles'])
			->getMock();

		// If module preparation succeeds, proceed with user file decryption
		$instance->expects($this->once())
			->method('prepareEncryptionModules')
			->with($this->mockInputInterface, $this->mockOutputInterface, self::TEST_USER)
			->willReturn(true);

		$instance->expects($this->once())
			->method('decryptAllUsersFiles')
			->with($this->mockOutputInterface, self::TEST_USER);

		$result = $instance->decryptAll($this->mockInputInterface, $this->mockOutputInterface, self::TEST_USER);
		$this->assertTrue($result);
	}

	/**
	 * Covers: early abort if module preparation fails.
	 * Ensures "decryptAllUsersFiles" is not called and function returns false.
	 */
	public function testDecryptAllAbortsIfPreparationFails(): void {
		$this->mockUserManager->method('userExists')->willReturn(true);

		$instance = $this->getMockBuilder(DecryptAll::class)
			->setConstructorArgs([
				$this->mockEncryptionManager,
				$this->mockUserManager,
				$this->mockView
			])
			->onlyMethods(['prepareEncryptionModules', 'decryptAllUsersFiles'])
			->getMock();

		$instance->expects($this->once())
			->method('prepareEncryptionModules')
			->with($this->mockInputInterface, $this->mockOutputInterface, self::TEST_USER)
			->willReturn(false);

		$instance->expects($this->never())
			->method('decryptAllUsersFiles');

		$result = $instance->decryptAll($this->mockInputInterface, $this->mockOutputInterface, self::TEST_USER);
		$this->assertFalse($result);
	}

	/**
	 * TODO: 
	 * - Add file operation failure (fileInfo/copy/rename)
	 * - Add teamfolder scenario
	 */
	
	/*********** LEGACY TESTS ************/
	
	public static function dataDecryptAll(): array {
		return [
			[true, 'user1', true],
			[false, 'user1', true],
			[true, '0', true],
			[false, '0', true],
			[true, '', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataDecryptAll')]
	public function testDecryptAll(bool $prepareResult, string $user, bool $userExistsChecked): void {
		if ($userExistsChecked) {
			$this->userManager->expects($this->once())->method('userExists')->willReturn(true);
		} else {
			$this->userManager->expects($this->never())->method('userExists');
		}
		/** @var DecryptAll&MockObject $instance */
		$instance = $this->getMockBuilder('OC\Encryption\DecryptAll')
			->setConstructorArgs(
				[
					$this->encryptionManager,
					$this->userManager,
					$this->view
				]
			)
			->onlyMethods(['prepareEncryptionModules', 'decryptAllUsersFiles'])
			->getMock();

		$instance->expects($this->once())
			->method('prepareEncryptionModules')
			->with($this->inputInterface, $this->outputInterface, $user)
			->willReturn($prepareResult);

		if ($prepareResult) {
			$instance->expects($this->once())
				->method('decryptAllUsersFiles')
				->with($this->outputInterface, $user);
		} else {
			$instance->expects($this->never())->method('decryptAllUsersFiles');
		}

		$instance->decryptAll($this->inputInterface, $this->outputInterface, $user);
	}

	/**
	 * test decrypt all call with a user who doesn't exists
	 */
	public function testDecryptAllWrongUser(): void {
		$this->userManager->expects($this->once())->method('userExists')->willReturn(false);
		$this->outputInterface->expects($this->once())->method('writeln')
			->with('User "user1" does not exist. Please check the username and try again');

		$this->assertFalse(
			$this->instance->decryptAll($this->inputInterface, $this->outputInterface, 'user1')
		);
	}

	public static function dataTrueFalse(): array {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @param bool $success
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTrueFalse')]
	public function testPrepareEncryptionModules($success): void {
		$user = 'user1';

		$dummyEncryptionModule = $this->getMockBuilder('OCP\Encryption\IEncryptionModule')
			->disableOriginalConstructor()->getMock();

		$dummyEncryptionModule->expects($this->once())
			->method('prepareDecryptAll')
			->with($this->inputInterface, $this->outputInterface, $user)
			->willReturn($success);

		$callback = function () use ($dummyEncryptionModule) {
			return $dummyEncryptionModule;
		};
		$moduleDescription = [
			'id' => 'id',
			'displayName' => 'displayName',
			'callback' => $callback
		];

		$this->encryptionManager->expects($this->once())
			->method('getEncryptionModules')
			->willReturn([$moduleDescription]);

		$this->assertSame($success,
			$this->invokePrivate($this->instance, 'prepareEncryptionModules', [$this->inputInterface, $this->outputInterface, $user])
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestDecryptAllUsersFiles')]
	public function testDecryptAllUsersFiles($user): void {
		/** @var DecryptAll&MockObject $instance */
		$instance = $this->getMockBuilder('OC\Encryption\DecryptAll')
			->setConstructorArgs(
				[
					$this->encryptionManager,
					$this->userManager,
					$this->view
				]
			)
			->onlyMethods(['decryptUsersFiles'])
			->getMock();

		if (empty($user)) {
			$this->userManager->expects($this->once())
				->method('getBackends')
				->willReturn([$this->userInterface]);
			$this->userInterface->expects($this->any())
				->method('getUsers')
				->willReturn(['user1', 'user2']);
			$calls = [
				'user1',
				'user2',
			];
			$instance->expects($this->exactly(2))
				->method('decryptUsersFiles')
				->willReturnCallback(function ($user) use (&$calls): void {
					$expected = array_shift($calls);
					$this->assertEquals($expected, $user);
				});
		} else {
			$instance->expects($this->once())
				->method('decryptUsersFiles')
				->with($user);
		}

		$this->invokePrivate($instance, 'decryptAllUsersFiles', [$this->outputInterface, $user]);
	}

	public static function dataTestDecryptAllUsersFiles(): array {
		return [
			['user1'],
			['']
		];
	}

	public function testDecryptUsersFiles(): void {
		/** @var DecryptAll | \PHPUnit\Framework\MockObject\MockObject  $instance */
		$instance = $this->getMockBuilder('OC\Encryption\DecryptAll')
			->setConstructorArgs(
				[
					$this->encryptionManager,
					$this->userManager,
					$this->view
				]
			)
			->onlyMethods(['decryptFile'])
			->getMock();

		$storage = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()->getMock();


		$sharedStorage = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()->getMock();

		$sharedStorage->expects($this->once())
			->method('instanceOfStorage')
			->with('OCA\Files_Sharing\SharedStorage')
			->willReturn(true);

		$this->view->expects($this->exactly(2))
			->method('getDirectoryContent')
			->willReturnMap([
				[
					'/user1/files', '', null,
					[
						new FileInfo('path', $storage, 'intPath', ['name' => 'foo', 'type' => 'dir'], null),
						new FileInfo('path', $storage, 'intPath', ['name' => 'bar', 'type' => 'file', 'encrypted' => true], null),
						new FileInfo('path', $sharedStorage, 'intPath', ['name' => 'shared', 'type' => 'file', 'encrypted' => true], null),
					],
				],
				[
					'/user1/files/foo', '', null,
					[
						new FileInfo('path', $storage, 'intPath', ['name' => 'subfile', 'type' => 'file', 'encrypted' => true], null)
					],
				],
			]);

		$this->view->expects($this->any())->method('is_dir')
			->willReturnCallback(
				function ($path) {
					if ($path === '/user1/files/foo') {
						return true;
					}
					return false;
				}
			);

		$calls = [
			'/user1/files/bar',
			'/user1/files/foo/subfile',
		];
		$instance->expects($this->exactly(2))
			->method('decryptFile')
			->willReturnCallback(function ($path) use (&$calls): bool {
				$expected = array_shift($calls);
				$this->assertEquals($expected, $path);
				return true;
			});


		/* We need format method to return a string */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturn('foo');

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->any())
			->method('getFormatter')
			->willReturn($outputFormatter);
		$progressBar = new ProgressBar($output);

		$this->invokePrivate($instance, 'decryptUsersFiles', ['user1', $progressBar, '']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTrueFalse')]
	public function testDecryptFile($isEncrypted): void {
		$path = 'test.txt';

		/** @var DecryptAll&MockObject $instance */
		$instance = $this->getMockBuilder('OC\Encryption\DecryptAll')
			->setConstructorArgs(
				[
					$this->encryptionManager,
					$this->userManager,
					$this->view
				]
			)
			->onlyMethods(['getTimestamp'])
			->getMock();

		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())->method('isEncrypted')
			->willReturn($isEncrypted);
		$this->view->expects($this->any())->method('getFileInfo')
			->willReturn($fileInfo);

		if ($isEncrypted) {
			$instance->expects($this->any())->method('getTimestamp')->willReturn(42);

			$this->view->expects($this->once())
				->method('copy')
				->with($path, $path . '.decrypted.42');
			$this->view->expects($this->once())
				->method('rename')
				->with($path . '.decrypted.42', $path);
		} else {
			$instance->expects($this->never())->method('getTimestamp');
			$this->view->expects($this->never())->method('copy');
			$this->view->expects($this->never())->method('rename');
		}
		$this->assertTrue(
			$this->invokePrivate($instance, 'decryptFile', [$path])
		);
	}

	public function testDecryptFileFailure(): void {
		$path = 'test.txt';

		/** @var DecryptAll&MockObject $instance */
		$instance = $this->getMockBuilder('OC\Encryption\DecryptAll')
			->setConstructorArgs(
				[
					$this->encryptionManager,
					$this->userManager,
					$this->view
				]
			)
			->onlyMethods(['getTimestamp'])
			->getMock();


		$fileInfo = $this->createMock(FileInfo::class);
		$fileInfo->expects($this->any())->method('isEncrypted')
			->willReturn(true);
		$this->view->expects($this->any())->method('getFileInfo')
			->willReturn($fileInfo);

		$instance->expects($this->any())->method('getTimestamp')->willReturn(42);

		$this->view->expects($this->once())
			->method('copy')
			->with($path, $path . '.decrypted.42')
			->willReturnCallback(function (): void {
				throw new DecryptionFailedException();
			});

		$this->view->expects($this->never())->method('rename');
		$this->view->expects($this->once())
			->method('file_exists')
			->with($path . '.decrypted.42')
			->willReturn(true);
		$this->view->expects($this->once())
			->method('unlink')
			->with($path . '.decrypted.42');

		$this->assertFalse(
			$this->invokePrivate($instance, 'decryptFile', [$path])
		);
	}
}
