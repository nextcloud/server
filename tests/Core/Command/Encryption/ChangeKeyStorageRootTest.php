<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Tests\Core\Command\Encryption;

use OC\Core\Command\Encryption\ChangeKeyStorageRoot;
use OC\Encryption\Keys\Storage;
use OC\Encryption\Util;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\IUserManager;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ChangeKeyStorageRootTest extends TestCase {
	protected ChangeKeyStorageRoot $changeKeyStorageRoot;

	protected IUserManager&\PHPUnit\Framework\MockObject\MockObject $userManager;

	protected Util&\PHPUnit\Framework\MockObject\MockObject $util;

	protected QuestionHelper&\PHPUnit\Framework\MockObject\MockObject $questionHelper;

	protected ISetupManager&\PHPUnit\Framework\MockObject\MockObject $setupManager;

	protected IRootFolder&\PHPUnit\Framework\MockObject\MockObject $rootFolder;

	/** @var InputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $inputInterface;

	/** @var OutputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $outputInterface;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		$this->util = $this->getMockBuilder('OC\Encryption\Util')->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)->getMock();
		$this->setupManager = $this->getMockBuilder(ISetupManager::class)->getMock();
		$this->rootFolder = $this->getMockBuilder(IRootFolder::class)->getMock();
		$this->inputInterface = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->outputInterface = $this->getMockBuilder(OutputInterface::class)->getMock();

		/* We need format method to return a string */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturnArgument(0);

		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($outputFormatter);

		$this->changeKeyStorageRoot = new ChangeKeyStorageRoot(
			$this->userManager,
			$this->util,
			$this->questionHelper,
			$this->setupManager,
			$this->rootFolder,
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestExecute')]
	public function testExecute($newRoot, $answer, $successMoveKey): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->userManager,
					$this->util,
					$this->questionHelper,
					$this->setupManager,
					$this->rootFolder,
				]
			)->onlyMethods(['moveAllKeys'])->getMock();

		$this->util->expects($this->once())->method('getKeyStorageRoot')
			->willReturn('');
		$this->inputInterface->expects($this->once())->method('getArgument')
			->with('newRoot')->willReturn($newRoot);

		if ($answer === true || $newRoot !== null) {
			$changeKeyStorageRoot->expects($this->once())->method('moveAllKeys')
				->willReturn($successMoveKey);
		} else {
			$changeKeyStorageRoot->expects($this->never())->method('moveAllKeys');
		}

		if ($successMoveKey === true) {
			$this->util->expects($this->once())->method('setKeyStorageRoot');
		} else {
			$this->util->expects($this->never())->method('setKeyStorageRoot');
		}

		if ($newRoot === null) {
			$this->questionHelper->expects($this->once())->method('ask')->willReturn($answer);
		} else {
			$this->questionHelper->expects($this->never())->method('ask');
		}

		$this->invokePrivate(
			$changeKeyStorageRoot,
			'execute',
			[$this->inputInterface, $this->outputInterface]
		);
	}

	public static function dataTestExecute(): array {
		return [
			[null, true, true],
			[null, true, false],
			[null, false, null],
			['/newRoot', null, true],
			['/newRoot', null, false]
		];
	}

	public function testMoveAllKeys(): void {
		/** @var ChangeKeyStorageRoot $changeKeyStorageRoot */
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->userManager,
					$this->util,
					$this->questionHelper,
					$this->setupManager,
					$this->rootFolder,
				]
			)->onlyMethods(['prepareNewRoot', 'moveSystemKeys', 'moveUserKeys'])->getMock();

		$oldRootFolder = $this->createMock(Folder::class);
		$this->rootFolder->expects($this->once())->method('get')
			->with('oldRoot')
			->willReturn($oldRootFolder);

		$changeKeyStorageRoot->expects($this->once())->method('prepareNewRoot')->with('newRoot');
		$changeKeyStorageRoot->expects($this->once())->method('moveSystemKeys')->with($oldRootFolder, 'newRoot');
		$changeKeyStorageRoot->expects($this->once())->method('moveUserKeys')->with($oldRootFolder, 'newRoot', $this->outputInterface);

		$this->invokePrivate($changeKeyStorageRoot, 'moveAllKeys', ['oldRoot', 'newRoot', $this->outputInterface]);
	}

	public function testPrepareNewRoot(): void {
		$this->rootFolder->expects($this->once())->method('nodeExists')->with('newRoot')
			->willReturn(true);

		$file = $this->createMock(File::class);
		$this->rootFolder->expects($this->once())->method('get')
			->with('newRoot/' . Storage::KEY_STORAGE_MARKER)
			->willReturn($file);
		$file->expects($this->once())->method('putContent')
			->with('Nextcloud will detect this folder as key storage root only if this file exists');

		$this->invokePrivate($this->changeKeyStorageRoot, 'prepareNewRoot', ['newRoot']);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestPrepareNewRootException')]
	public function testPrepareNewRootException(bool $dirExists, bool $putContentFails): void {
		$this->expectException(\Exception::class);

		$this->rootFolder->method('nodeExists')->with('newRoot')->willReturn($dirExists);

		if ($dirExists) {
			$file = $this->createMock(File::class);
			$this->rootFolder->method('get')->willReturn($file);
			if ($putContentFails) {
				$file->method('putContent')->willThrowException(new \Exception('write error'));
			}
		}

		$this->invokePrivate($this->changeKeyStorageRoot, 'prepareNewRoot', ['newRoot']);
	}

	public static function dataTestPrepareNewRootException(): array {
		return [
			[false, false],
			[true, true],
		];
	}

	/**
	 *
	 * @param bool $folderExists
	 * @param bool $targetExists
	 * @param bool $executeMove
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestMoveSystemKeys')]
	public function testMoveSystemKeys(bool $folderExists, bool $targetExists, bool $executeMove): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->userManager,
					$this->util,
					$this->questionHelper,
					$this->setupManager,
					$this->rootFolder,
				]
			)->onlyMethods(['targetExists'])->getMock();

		$oldRoot = $this->createMock(Folder::class);
		$fileEncryptionNode = $this->createMock(Folder::class);

		if ($folderExists) {
			$oldRoot->method('get')->with('files_encryption')->willReturn($fileEncryptionNode);
		} else {
			$oldRoot->method('get')->willThrowException(new NotFoundException());
		}

		$changeKeyStorageRoot->method('targetExists')
			->with('newRoot/files_encryption')
			->willReturn($targetExists);

		if ($executeMove) {
			$fileEncryptionNode->expects($this->once())->method('move')
				->with('newRoot/files_encryption');
		} else {
			$fileEncryptionNode->expects($this->never())->method('move');
		}

		$this->invokePrivate($changeKeyStorageRoot, 'moveSystemKeys', [$oldRoot, 'newRoot']);
	}

	public static function dataTestMoveSystemKeys(): array {
		return [
			[true, false, true],
			[true, true, false],
			[false, false, false],
		];
	}

	public function testMoveUserKeys(): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->userManager,
					$this->util,
					$this->questionHelper,
					$this->setupManager,
					$this->rootFolder,
				]
			)->onlyMethods(['setupUserFS', 'moveUserEncryptionFolder'])->getMock();

		$oldRootFolder = $this->createMock(Folder::class);

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);

		$this->userManager->expects($this->once())->method('callForAllUsers')
			->willReturnCallback(function (callable $callback) use ($user1, $user2): void {
				$callback($user1);
				$callback($user2);
			});

		$changeKeyStorageRoot->expects($this->exactly(2))->method('setupUserFS');
		$changeKeyStorageRoot->expects($this->exactly(2))->method('moveUserEncryptionFolder');

		$this->invokePrivate($changeKeyStorageRoot, 'moveUserKeys', [$oldRootFolder, 'newRoot', $this->outputInterface]);
	}

	/**
	 *
	 * @param bool $folderExists
	 * @param bool $targetExists
	 * @param bool $shouldMove
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestMoveUserEncryptionFolder')]
	public function testMoveUserEncryptionFolder(bool $folderExists, bool $targetExists, bool $shouldMove): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->userManager,
					$this->util,
					$this->questionHelper,
					$this->setupManager,
					$this->rootFolder,
				]
			)->onlyMethods(['targetExists', 'prepareParentFolder'])->getMock();

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');

		$oldRootFolder = $this->createMock(Folder::class);
		$fileEncryptionFolder = $this->createMock(Folder::class);

		if ($folderExists) {
			$oldRootFolder->method('get')
				->with('user1/files_encryption')
				->willReturn($fileEncryptionFolder);
		} else {
			$oldRootFolder->method('get')->willThrowException(new NotFoundException());
		}

		$changeKeyStorageRoot->method('targetExists')->willReturn($targetExists);

		if ($shouldMove) {
			$changeKeyStorageRoot->expects($this->once())->method('prepareParentFolder')
				->with('newRoot/user1');
			$fileEncryptionFolder->expects($this->once())->method('move')
				->with('newRoot/user1/files_encryption');
		} else {
			$changeKeyStorageRoot->expects($this->never())->method('prepareParentFolder');
			$fileEncryptionFolder->expects($this->never())->method('move');
		}

		$this->invokePrivate($changeKeyStorageRoot, 'moveUserEncryptionFolder', [$user, $oldRootFolder, 'newRoot']);
	}

	public static function dataTestMoveUserEncryptionFolder(): array {
		return [
			[true, false, true],
			[true, true, false],
			[false, false, false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTestPrepareParentFolder')]
	public function testPrepareParentFolder($path, $pathExists): void {
		$this->rootFolder->expects($this->any())->method('nodeExists')
			->willReturnCallback(
				function (string $nodeExistsPath) use ($path, $pathExists): bool {
					if ($path === $nodeExistsPath) {
						return $pathExists;
					}
					return false;
				}
			);

		if ($pathExists === false) {
			$subDirs = explode('/', ltrim($path, '/'));
			$this->rootFolder->expects($this->exactly(count($subDirs)))->method('newFolder');
		} else {
			$this->rootFolder->expects($this->never())->method('newFolder');
		}

		$this->invokePrivate(
			$this->changeKeyStorageRoot,
			'prepareParentFolder',
			[$path]
		);
	}

	public static function dataTestPrepareParentFolder(): array {
		return [
			['/user/folder/sub_folder/keystorage', true],
			['/user/folder/sub_folder/keystorage', false]
		];
	}

	public function testTargetExists(): void {
		$this->rootFolder->expects($this->once())->method('get')->with('path')
			->willThrowException(new NotFoundException());

		$this->assertFalse(
			$this->invokePrivate($this->changeKeyStorageRoot, 'targetExists', ['path'])
		);
	}

	public function testTargetExistsException(): void {
		$this->expectException(\Exception::class);

		$node = $this->createMock(\OCP\Files\Node::class);
		$this->rootFolder->expects($this->once())->method('get')->with('path')
			->willReturn($node);

		$this->invokePrivate($this->changeKeyStorageRoot, 'targetExists', ['path']);
	}
}
