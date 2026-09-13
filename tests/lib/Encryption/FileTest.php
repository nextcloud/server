<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Encryption;

use OC\Encryption\File;
use OC\Encryption\Util;
use OCP\App\IAppManager;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FileTest extends TestCase {
	private Util&MockObject $util;
	private IManager&MockObject $shareManager;
	private IUserFolder&MockObject $userFolder;
	private File&MockObject $file;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->util = $this->createMock(Util::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->userFolder = $this->createMock(IUserFolder::class);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')
			->with('user1')
			->willReturn($this->userFolder);

		$appManager = $this->createMock(IAppManager::class);
		$appManager->method('isEnabledForUser')
			->willReturn(false);

		$this->file = $this->getMockBuilder(File::class)
			->setConstructorArgs([$this->util, $rootFolder, $this->shareManager])
			->onlyMethods(['getAppManager'])
			->getMock();
		$this->file->method('getAppManager')
			->willReturn($appManager);
	}

	/**
	 * Let the util mock resolve every path to $owner and $ownerPath
	 */
	private function mockUtil(string $owner, string $ownerPath): void {
		$this->util->method('getUidAndFilename')
			->willReturn([$owner, $ownerPath]);
		$this->util->method('isFile')
			->willReturn(true);
		$this->util->method('stripPartialFileExtension')
			->willReturnArgument(0);
	}

	public function testGetAccessList(): void {
		$this->mockUtil('user1', '/files/folder/file.txt');

		$node = $this->createMock(Node::class);
		$parentNode = $this->createMock(Node::class);
		$this->userFolder->method('get')
			->willReturnMap([
				['/folder/file.txt', $node],
				['/folder', $parentNode],
			]);

		$this->shareManager->method('getAccessList')
			->willReturnCallback(fn (Node $requested) => match ($requested) {
				$parentNode => ['users' => ['user2'], 'public' => false, 'remote' => false],
				$node => ['users' => ['user3'], 'public' => true, 'remote' => false],
			});

		$this->assertSame(
			['users' => ['user1', 'user2', 'user3'], 'public' => true],
			$this->file->getAccessList('/user1/files/folder/file.txt')
		);
	}

	/**
	 * Copying a folder creates the target directories on the storage before their
	 * cache entries exist, so the parent of a file written into it can not be resolved.
	 */
	public function testGetAccessListWithUncachedParent(): void {
		$this->mockUtil('user1', '/files/target/sub');

		$rootNode = $this->createMock(Node::class);
		$this->userFolder->method('get')
			->willReturnCallback(function (string $path) use ($rootNode) {
				if ($path !== '/') {
					throw new NotFoundException($path);
				}
				return $rootNode;
			});

		$this->shareManager->expects($this->once())
			->method('getAccessList')
			->with($rootNode)
			->willReturn(['users' => ['user2'], 'public' => false, 'remote' => false]);

		$this->assertSame(
			['users' => ['user1', 'user2'], 'public' => false],
			$this->file->getAccessList('/user1/files/target/sub')
		);
	}

	public function testGetAccessListWithoutAnyResolvablePath(): void {
		$this->mockUtil('user1', '/files/target/sub');

		$this->userFolder->method('get')
			->willThrowException(new NotFoundException());

		$this->shareManager->expects($this->never())
			->method('getAccessList');

		$this->assertSame(
			['users' => ['user1'], 'public' => false],
			$this->file->getAccessList('/user1/files/target/sub')
		);
	}

	public function testGetAccessListCachesTheParentResult(): void {
		$this->util->method('getUidAndFilename')
			->willReturnCallback(fn (string $path) => ['user1', substr($path, strlen('/user1'))]);
		$this->util->method('isFile')
			->willReturn(true);
		$this->util->method('stripPartialFileExtension')
			->willReturnArgument(0);

		$parentNode = $this->createMock(Node::class);
		$this->userFolder->method('get')
			->willReturnCallback(function (string $path) use ($parentNode) {
				if ($path !== '/folder') {
					throw new NotFoundException($path);
				}
				return $parentNode;
			});

		$this->shareManager->expects($this->once())
			->method('getAccessList')
			->with($parentNode)
			->willReturn(['users' => ['user2'], 'public' => false, 'remote' => false]);

		$expected = ['users' => ['user1', 'user2'], 'public' => false];
		$this->assertSame($expected, $this->file->getAccessList('/user1/files/folder/first.txt'));
		$this->assertSame($expected, $this->file->getAccessList('/user1/files/folder/second.txt'));
	}
}
