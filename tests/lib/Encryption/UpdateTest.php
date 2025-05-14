<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Encryption;

use OC\Encryption\File;
use OC\Encryption\Update;
use OC\Encryption\Util;
use OC\Files\View;
use OCP\Encryption\IEncryptionModule;
use OCP\Files\File as OCPFile;
use OCP\Files\Folder;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UpdateTest extends TestCase {
	private string $uid;
	private View&MockObject $view;
	private Util&MockObject $util;
	private \OC\Encryption\Manager&MockObject $encryptionManager;
	private IEncryptionModule&MockObject $encryptionModule;
	private File&MockObject $fileHelper;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->view = $this->createMock(View::class);
		$this->util = $this->createMock(Util::class);
		$this->encryptionManager = $this->createMock(\OC\Encryption\Manager::class);
		$this->fileHelper = $this->createMock(File::class);
		$this->encryptionModule = $this->createMock(IEncryptionModule::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->uid = 'testUser1';
	}

	private function getUserMock(string $uid): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->expects(self::any())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}

	private function getFileMock(string $path, string $owner): OCPFile&MockObject {
		$node = $this->createMock(OCPFile::class);
		$node->expects(self::atLeastOnce())
			->method('getPath')
			->willReturn($path);
		$node->expects(self::any())
			->method('getOwner')
			->willReturn($this->getUserMock($owner));

		return $node;
	}

	private function getFolderMock(string $path, string $owner): Folder&MockObject {
		$node = $this->createMock(Folder::class);
		$node->expects(self::atLeastOnce())
			->method('getPath')
			->willReturn($path);
		$node->expects(self::any())
			->method('getOwner')
			->willReturn($this->getUserMock($owner));

		return $node;
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
		$updateMock = $this->getUpdateMock(['getOwnerPath']);
		$updateMock->expects($this->once())->method('getOwnerPath')
			->willReturnCallback(fn (OCPFile|Folder $node) => '/user/' . $node->getPath());

		$this->encryptionManager->expects($this->once())
			->method('getEncryptionModule')
			->willReturn($this->encryptionModule);

		if ($isDir) {
			$this->util->expects($this->once())
				->method('getAllFiles')
				->willReturn($allFiles);
			$node = $this->getFolderMock($path, 'user');
		} else {
			$node = $this->getFileMock($path, 'user');
		}

		$this->fileHelper->expects($this->exactly($numberOfFiles))
			->method('getAccessList')
			->willReturn(['users' => [], 'public' => false]);

		$this->encryptionModule->expects($this->exactly($numberOfFiles))
			->method('update')
			->willReturn(true);

		$updateMock->update($node);
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
	 */
	public function testPostRename($source, $target): void {
		$updateMock = $this->getUpdateMock(['update','getOwnerPath']);

		$sourceNode = $this->getFileMock($source, 'user');
		$targetNode = $this->getFileMock($target, 'user');

		if (dirname($source) === dirname($target)) {
			$updateMock->expects($this->never())->method('getOwnerPath');
			$updateMock->expects($this->never())->method('update');
		} else {
			$updateMock->expects($this->once())->method('update')
				->willReturnCallback(fn (OCPFile|Folder $node) => $this->assertSame(
					$target,
					$node->getPath(),
					'update needs to be executed for the target destination'
				));
		}

		$updateMock->postRename($sourceNode, $targetNode);
	}

	/**
	 * test data for testPostRename()
	 *
	 * @return array
	 */
	public function dataTestPostRename() {
		return [
			['/test.txt', '/testNew.txt'],
			['/folder/test.txt', '/testNew.txt'],
			['/test.txt', '/folder/testNew.txt'],
		];
	}

	public function testPostRestore(): void {
		$updateMock = $this->getUpdateMock(['update']);

		$updateMock->expects($this->once())->method('update')
			->willReturnCallback(fn (OCPFile|Folder $node) => $this->assertSame(
				'/folder/test.txt',
				$node->getPath(),
				'update needs to be executed for the target destination'
			));

		$updateMock->postRestore($this->getFileMock('/folder/test.txt', 'user'));
	}

	/**
	 * create mock of the update method
	 *
	 * @param array $methods methods which should be set
	 */
	protected function getUpdateMock(array $methods): Update&MockObject {
		return  $this->getMockBuilder(Update::class)
			->setConstructorArgs(
				[
					$this->util,
					$this->encryptionManager,
					$this->fileHelper,
					$this->logger,
					$this->uid
				]
			)->setMethods($methods)->getMock();
	}
}
