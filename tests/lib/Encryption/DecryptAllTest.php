<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\DecryptAll;
use OC\Encryption\Exceptions\DecryptionFailedException;
use OC\Encryption\Manager;
use OC\Files\FileInfo;
use OC\Files\View;
use OCP\Files\Storage\IStorage;
use OCP\IUserManager;
use OCP\UserInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

/**
 * Class DecryptAllTest
 *
 * @group DB
 *
 * @package Test\Encryption
 */
class DecryptAllTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject | IUserManager */
	protected $userManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject | Manager */
	protected $encryptionManager;

	/** @var \PHPUnit\Framework\MockObject\MockObject | View */
	protected $view;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Input\InputInterface */
	protected $inputInterface;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \Symfony\Component\Console\Output\OutputInterface */
	protected $outputInterface;

	/** @var \PHPUnit\Framework\MockObject\MockObject | \OCP\UserInterface */
	protected $userInterface;

	/** @var DecryptAll */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->getMockBuilder(IUserManager::class)
			->disableOriginalConstructor()->getMock();
		$this->encryptionManager = $this->getMockBuilder('OC\Encryption\Manager')
			->disableOriginalConstructor()->getMock();
		$this->view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()->getMock();
		$this->inputInterface = $this->getMockBuilder(InputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->outputInterface = $this->getMockBuilder(OutputInterface::class)
			->disableOriginalConstructor()->getMock();
		$this->outputInterface->expects($this->any())->method('isDecorated')
			->willReturn(false);
		$this->userInterface = $this->getMockBuilder(UserInterface::class)
			->disableOriginalConstructor()->getMock();

		/* We need format method to return a string */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('format')->willReturn('foo');
		$outputFormatter->method('isDecorated')->willReturn(false);

		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($outputFormatter);

		$this->instance = new DecryptAll($this->encryptionManager, $this->userManager, $this->view);

		$this->invokePrivate($this->instance, 'input', [$this->inputInterface]);
		$this->invokePrivate($this->instance, 'output', [$this->outputInterface]);
	}

	public static function dataDecryptAll(): array {
		return [
			[true, 'user1', true],
			[false, 'user1', true],
			[true, '0', true],
			[false, '0', true],
			[true, '', false],
		];
	}

	/**
	 * @dataProvider dataDecryptAll
	 * @param bool $prepareResult
	 * @param string $user
	 * @param bool $userExistsChecked
	 */
	public function testDecryptAll($prepareResult, $user, $userExistsChecked): void {
		if ($userExistsChecked) {
			$this->userManager->expects($this->once())->method('userExists')->willReturn(true);
		} else {
			$this->userManager->expects($this->never())->method('userExists');
		}
		/** @var DecryptAll | \PHPUnit\Framework\MockObject\MockObject |  $instance */
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
			->with($user)
			->willReturn($prepareResult);

		if ($prepareResult) {
			$instance->expects($this->once())
				->method('decryptAllUsersFiles')
				->with($user);
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
	 * @dataProvider dataTrueFalse
	 * @param bool $success
	 */
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
			$this->invokePrivate($this->instance, 'prepareEncryptionModules', [$user])
		);
	}

	/**
	 * @dataProvider dataTestDecryptAllUsersFiles
	 */
	public function testDecryptAllUsersFiles($user): void {
		/** @var DecryptAll | \PHPUnit\Framework\MockObject\MockObject |  $instance */
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

		$this->invokePrivate($instance, 'input', [$this->inputInterface]);
		$this->invokePrivate($instance, 'output', [$this->outputInterface]);

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

		$this->invokePrivate($instance, 'decryptAllUsersFiles', [$user]);
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
			->willReturnCallback(function ($path) use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, $path);
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

	/**
	 * @dataProvider dataTrueFalse
	 */
	public function testDecryptFile($isEncrypted): void {
		$path = 'test.txt';

		/** @var DecryptAll | \PHPUnit\Framework\MockObject\MockObject  $instance */
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

		/** @var DecryptAll | \PHPUnit\Framework\MockObject\MockObject  $instance */
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
