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
use OC\Files\View;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\UserInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class ChangeKeyStorageRootTest extends TestCase {
	/** @var ChangeKeyStorageRoot */
	protected $changeKeyStorageRoot;

	/** @var View | \PHPUnit\Framework\MockObject\MockObject */
	protected $view;

	/** @var IUserManager | \PHPUnit\Framework\MockObject\MockObject */
	protected $userManager;

	/** @var IConfig | \PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var Util | \PHPUnit\Framework\MockObject\MockObject */
	protected $util;

	/** @var QuestionHelper | \PHPUnit\Framework\MockObject\MockObject */
	protected $questionHelper;

	/** @var InputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $inputInterface;

	/** @var OutputInterface | \PHPUnit\Framework\MockObject\MockObject */
	protected $outputInterface;

	/** @var \OCP\UserInterface |  \PHPUnit\Framework\MockObject\MockObject */
	protected $userInterface;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->getMockBuilder(View::class)->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->util = $this->getMockBuilder('OC\Encryption\Util')->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder(QuestionHelper::class)->getMock();
		$this->inputInterface = $this->getMockBuilder(InputInterface::class)->getMock();
		$this->outputInterface = $this->getMockBuilder(OutputInterface::class)->getMock();
		$this->userInterface = $this->getMockBuilder(UserInterface::class)->getMock();

		/* We need format method to return a string */
		$outputFormatter = $this->createMock(OutputFormatterInterface::class);
		$outputFormatter->method('isDecorated')->willReturn(false);
		$outputFormatter->method('format')->willReturnArgument(0);

		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($outputFormatter);

		$this->changeKeyStorageRoot = new ChangeKeyStorageRoot(
			$this->view,
			$this->userManager,
			$this->config,
			$this->util,
			$this->questionHelper
		);
	}

	/**
	 * @dataProvider dataTestExecute
	 */
	public function testExecute($newRoot, $answer, $successMoveKey): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->view,
					$this->userManager,
					$this->config,
					$this->util,
					$this->questionHelper
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
		/** @var \OC\Core\Command\Encryption\ChangeKeyStorageRoot $changeKeyStorageRoot */
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->view,
					$this->userManager,
					$this->config,
					$this->util,
					$this->questionHelper
				]
			)->onlyMethods(['prepareNewRoot', 'moveSystemKeys', 'moveUserKeys'])->getMock();

		$changeKeyStorageRoot->expects($this->once())->method('prepareNewRoot')->with('newRoot');
		$changeKeyStorageRoot->expects($this->once())->method('moveSystemKeys')->with('oldRoot', 'newRoot');
		$changeKeyStorageRoot->expects($this->once())->method('moveUserKeys')->with('oldRoot', 'newRoot', $this->outputInterface);

		$this->invokePrivate($changeKeyStorageRoot, 'moveAllKeys', ['oldRoot', 'newRoot', $this->outputInterface]);
	}

	public function testPrepareNewRoot(): void {
		$this->view->expects($this->once())->method('is_dir')->with('newRoot')
			->willReturn(true);

		$this->view->expects($this->once())->method('file_put_contents')
			->with('newRoot/' . Storage::KEY_STORAGE_MARKER,
				'Nextcloud will detect this folder as key storage root only if this file exists')->willReturn(true);

		$this->invokePrivate($this->changeKeyStorageRoot, 'prepareNewRoot', ['newRoot']);
	}

	/**
	 * @dataProvider dataTestPrepareNewRootException
	 *
	 * @param bool $dirExists
	 * @param bool $couldCreateFile
	 */
	public function testPrepareNewRootException($dirExists, $couldCreateFile): void {
		$this->expectException(\Exception::class);

		$this->view->expects($this->once())->method('is_dir')->with('newRoot')
			->willReturn($dirExists);
		$this->view->expects($this->any())->method('file_put_contents')->willReturn($couldCreateFile);

		$this->invokePrivate($this->changeKeyStorageRoot, 'prepareNewRoot', ['newRoot']);
	}

	public static function dataTestPrepareNewRootException(): array {
		return [
			[true, false],
			[true, null],
			[false, true]
		];
	}

	/**
	 * @dataProvider dataTestMoveSystemKeys
	 *
	 * @param bool $dirExists
	 * @param bool $targetExists
	 * @param bool $executeRename
	 */
	public function testMoveSystemKeys($dirExists, $targetExists, $executeRename): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->view,
					$this->userManager,
					$this->config,
					$this->util,
					$this->questionHelper
				]
			)->onlyMethods(['targetExists'])->getMock();

		$this->view->expects($this->once())->method('is_dir')
			->with('oldRoot/files_encryption')->willReturn($dirExists);
		$changeKeyStorageRoot->expects($this->any())->method('targetExists')
			->with('newRoot/files_encryption')->willReturn($targetExists);

		if ($executeRename) {
			$this->view->expects($this->once())->method('rename')
				->with('oldRoot/files_encryption', 'newRoot/files_encryption');
		} else {
			$this->view->expects($this->never())->method('rename');
		}

		$this->invokePrivate($changeKeyStorageRoot, 'moveSystemKeys', ['oldRoot', 'newRoot']);
	}

	public static function dataTestMoveSystemKeys(): array {
		return [
			[true, false, true],
			[false, true, false],
			[true, true, false],
			[false, false, false]
		];
	}


	public function testMoveUserKeys(): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->view,
					$this->userManager,
					$this->config,
					$this->util,
					$this->questionHelper
				]
			)->onlyMethods(['setupUserFS', 'moveUserEncryptionFolder'])->getMock();

		$this->userManager->expects($this->once())->method('getBackends')
			->willReturn([$this->userInterface]);
		$this->userInterface->expects($this->once())->method('getUsers')
			->willReturn(['user1', 'user2']);
		$changeKeyStorageRoot->expects($this->exactly(2))->method('setupUserFS');
		$changeKeyStorageRoot->expects($this->exactly(2))->method('moveUserEncryptionFolder');

		$this->invokePrivate($changeKeyStorageRoot, 'moveUserKeys', ['oldRoot', 'newRoot', $this->outputInterface]);
	}

	/**
	 * @dataProvider dataTestMoveUserEncryptionFolder
	 *
	 * @param bool $userExists
	 * @param bool $isDir
	 * @param bool $targetExists
	 * @param bool $shouldRename
	 */
	public function testMoveUserEncryptionFolder($userExists, $isDir, $targetExists, $shouldRename): void {
		$changeKeyStorageRoot = $this->getMockBuilder('OC\Core\Command\Encryption\ChangeKeyStorageRoot')
			->setConstructorArgs(
				[
					$this->view,
					$this->userManager,
					$this->config,
					$this->util,
					$this->questionHelper
				]
			)->onlyMethods(['targetExists', 'prepareParentFolder'])->getMock();

		$this->userManager->expects($this->once())->method('userExists')
			->willReturn($userExists);
		$this->view->expects($this->any())->method('is_dir')
			->willReturn($isDir);
		$changeKeyStorageRoot->expects($this->any())->method('targetExists')
			->willReturn($targetExists);

		if ($shouldRename) {
			$changeKeyStorageRoot->expects($this->once())->method('prepareParentFolder')
				->with('newRoot/user1');
			$this->view->expects($this->once())->method('rename')
				->with('oldRoot/user1/files_encryption', 'newRoot/user1/files_encryption');
		} else {
			$changeKeyStorageRoot->expects($this->never())->method('prepareParentFolder');
			$this->view->expects($this->never())->method('rename');
		}

		$this->invokePrivate($changeKeyStorageRoot, 'moveUserEncryptionFolder', ['user1', 'oldRoot', 'newRoot']);
	}

	public static function dataTestMoveUserEncryptionFolder(): array {
		return [
			[true, true, false, true],
			[true, false, true, false],
			[false, true, true, false],
			[false, false, true, false],
			[false, true, false, false],
			[false, true, true, false],
			[false, false, false, false]
		];
	}


	/**
	 * @dataProvider dataTestPrepareParentFolder
	 */
	public function testPrepareParentFolder($path, $pathExists): void {
		$this->view->expects($this->any())->method('file_exists')
			->willReturnCallback(
				function ($fileExistsPath) use ($path, $pathExists) {
					if ($path === $fileExistsPath) {
						return $pathExists;
					}
					return false;
				}
			);

		if ($pathExists === false) {
			$subDirs = explode('/', ltrim($path, '/'));
			$this->view->expects($this->exactly(count($subDirs)))->method('mkdir');
		} else {
			$this->view->expects($this->never())->method('mkdir');
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
		$this->view->expects($this->once())->method('file_exists')->with('path')
			->willReturn(false);

		$this->assertFalse(
			$this->invokePrivate($this->changeKeyStorageRoot, 'targetExists', ['path'])
		);
	}


	public function testTargetExistsException(): void {
		$this->expectException(\Exception::class);

		$this->view->expects($this->once())->method('file_exists')->with('path')
			->willReturn(true);

		$this->invokePrivate($this->changeKeyStorageRoot, 'targetExists', ['path']);
	}
}
