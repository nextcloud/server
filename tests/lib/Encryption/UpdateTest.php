<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\File;
use OC\Encryption\Update;
use OC\Encryption\Util;
use OC\Files\Mount\Manager;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UpdateTest extends TestCase {
	/** @var \OC\Encryption\Update */
	private $update;

	/** @var string */
	private $uid;

	/** @var \OC\Files\View | \PHPUnit\Framework\MockObject\MockObject */
	private $view;

	/** @var Util | \PHPUnit\Framework\MockObject\MockObject */
	private $util;

	/** @var \OC\Files\Mount\Manager | \PHPUnit\Framework\MockObject\MockObject */
	private $mountManager;

	/** @var \OC\Encryption\Manager | \PHPUnit\Framework\MockObject\MockObject */
	private $encryptionManager;

	/** @var \OCP\Encryption\IEncryptionModule | \PHPUnit\Framework\MockObject\MockObject */
	private $encryptionModule;

	/** @var \OC\Encryption\File | \PHPUnit\Framework\MockObject\MockObject */
	private $fileHelper;

	/** @var \PHPUnit\Framework\MockObject\MockObject|LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->createMock(View::class);
		$this->util = $this->createMock(Util::class);
		$this->mountManager = $this->createMock(Manager::class);
		$this->encryptionManager = $this->createMock(\OC\Encryption\Manager::class);
		$this->fileHelper = $this->createMock(File::class);
		$this->encryptionModule = $this->createMock(IEncryptionModule::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->uid = 'testUser1';

		$this->update = new Update(
			$this->view,
			$this->util,
			$this->mountManager,
			$this->encryptionManager,
			$this->fileHelper,
			$this->logger,
			$this->uid);
	}

	/**
	 * @dataProvider dataTestUpdate
	 *
	 * @param string $path
	 * @param boolean $isDir
	 * @param array $allFiles
	 * @param integer $numberOfFiles
	 */
	public function testUpdate($path, $isDir, $allFiles, $numberOfFiles): void {
		$this->encryptionManager->expects($this->once())
			->method('getEncryptionModule')
			->willReturn($this->encryptionModule);

		$this->view->expects($this->once())
			->method('is_dir')
			->willReturn($isDir);

		if ($isDir) {
			$this->util->expects($this->once())
				->method('getAllFiles')
				->willReturn($allFiles);
		}

		$this->fileHelper->expects($this->exactly($numberOfFiles))
			->method('getAccessList')
			->willReturn(['users' => [], 'public' => false]);

		$this->encryptionModule->expects($this->exactly($numberOfFiles))
			->method('update')
			->willReturn(true);

		$this->update->update($path);
	}

	/**
	 * data provider for testUpdate()
	 *
	 * @return array
	 */
	public function dataTestUpdate() {
		return [
			['/user/files/foo', true, ['/user/files/foo/file1.txt', '/user/files/foo/file1.txt'], 2],
			['/user/files/test.txt', false, [], 1],
		];
	}

	/**
	 * @dataProvider dataTestPostRename
	 *
	 * @param string $source
	 * @param string $target
	 * @param boolean $encryptionEnabled
	 */
	public function testPostRename($source, $target, $encryptionEnabled): void {
		$updateMock = $this->getUpdateMock(['update', 'getOwnerPath']);

		$this->encryptionManager->expects($this->once())
			->method('isEnabled')
			->willReturn($encryptionEnabled);

		if (dirname($source) === dirname($target) || $encryptionEnabled === false) {
			$updateMock->expects($this->never())->method('getOwnerPath');
			$updateMock->expects($this->never())->method('update');
		} else {
			$updateMock->expects($this->once())
				->method('getOwnerPath')
				->willReturnCallback(function ($path) use ($target) {
					$this->assertSame(
						$target,
						$path,
						'update needs to be executed for the target destination');
					return ['owner', $path];
				});
			$updateMock->expects($this->once())->method('update');
		}

		$updateMock->postRename(['oldpath' => $source, 'newpath' => $target]);
	}

	/**
	 * test data for testPostRename()
	 *
	 * @return array
	 */
	public function dataTestPostRename() {
		return [
			['/test.txt', '/testNew.txt', true],
			['/test.txt', '/testNew.txt', false],
			['/folder/test.txt', '/testNew.txt', true],
			['/folder/test.txt', '/testNew.txt', false],
			['/folder/test.txt', '/testNew.txt', true],
			['/test.txt', '/folder/testNew.txt', false],
		];
	}


	/**
	 * @dataProvider dataTestPostRestore
	 *
	 * @param boolean $encryptionEnabled
	 */
	public function testPostRestore($encryptionEnabled): void {
		$updateMock = $this->getUpdateMock(['update']);

		$this->encryptionManager->expects($this->once())
			->method('isEnabled')
			->willReturn($encryptionEnabled);

		if ($encryptionEnabled) {
			$updateMock->expects($this->once())->method('update');
		} else {
			$updateMock->expects($this->never())->method('update');
		}

		$updateMock->postRestore(['filePath' => '/folder/test.txt']);
	}

	/**
	 * test data for testPostRestore()
	 *
	 * @return array
	 */
	public function dataTestPostRestore() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * create mock of the update method
	 *
	 * @param array $methods methods which should be set
	 * @return \OC\Encryption\Update | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getUpdateMock($methods) {
		return  $this->getMockBuilder('\OC\Encryption\Update')
			->setConstructorArgs(
				[
					$this->view,
					$this->util,
					$this->mountManager,
					$this->encryptionManager,
					$this->fileHelper,
					$this->logger,
					$this->uid
				]
			)->setMethods($methods)->getMock();
	}
}
