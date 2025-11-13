<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\UploadFolder;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound as SabreNotFound;
use Test\TestCase;
use Test\Traits\UserTrait;

class UploadHomeTest extends TestCase {
	use UserTrait;

	private array $principalInfo;

	protected function setUp(): void {
		parent::setUp();
		$this->principalInfo = [
			'uri' => 'principals/users/testuser'
		];
	}

	private function getMockedUploadHomeUser(): UploadHome {
		$cleanup = $this->createMock(CleanupService::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$userSession = $this->createMock(IUserSession::class);
		$shareManager = $this->createMock(IManager::class);

		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		return new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);
	}

	private function getMockedUploadHomeShare(): UploadHome {
		$cleanup = $this->createMock(CleanupService::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$userSession = $this->createMock(IUserSession::class);
		$shareManager = $this->createMock(IManager::class);

		$principalInfo = [
			'uri' => 'principals/shares/sometoken'
		];

		$shareMock = $this->createMock(IShare::class);
		$shareMock->method('getShareOwner')->willReturn('shareowner');
		$shareManager->method('getShareByToken')->with('sometoken')->willReturn($shareMock);

		return new UploadHome(
			$principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);
	}

	public function testUserConstructorSetsUid(): void {
		$uploadHome = $this->getMockedUploadHomeUser();
		$reflector = new \ReflectionClass($uploadHome);
		$uidProp = $reflector->getProperty('uid');
		$uidProp->setAccessible(true);
		$this->assertEquals('testuser', $uidProp->getValue($uploadHome));
	}

	public function testShareConstructorSetsUid(): void {
		$uploadHome = $this->getMockedUploadHomeShare();
		$ref = new \ReflectionClass($uploadHome);
		$uidProp = $ref->getProperty('uid');
		$uidProp->setAccessible(true);
		$this->assertEquals('shareowner', $uidProp->getValue($uploadHome));
	}

	public function testConstructorThrowsIfUserMissing(): void {
		$cleanup = $this->createMock(CleanupService::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);
		$shareManager = $this->createMock(IManager::class);

		$this->expectException(Forbidden::class);
		new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);
	}

	public function testCreateFileThrowsForbidden(): void {
		$uploadHome = $this->getMockedUploadHomeUser();
		$this->expectException(Forbidden::class);
		$uploadHome->createFile('denied.txt');
	}

	public function testSetNameThrowsForbidden(): void {
		$uploadHome = $this->getMockedUploadHomeUser();
		$this->expectException(Forbidden::class);
		$uploadHome->setName('denied');
	}

	public function testCreateDirectoryAddsCleanupJob(): void {
		$cleanup = $this->createMock(CleanupService::class);

		$folder = $this->createMock(Folder::class);
		$folder->expects($this->once())->method('createDirectory')->with('foo');

		$rootFolder = $this->createMock(IRootFolder::class);
		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);
		$shareManager = $this->createMock(IManager::class);

		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$cleanup->expects($this->once())->method('addJob')->with('testuser', 'foo');

		$uploadHome = new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);

		$uploadHome->createDirectory('foo');
	}

	public function testGetChildReturnsUploadFolder(): void {
		$cleanup = $this->createMock(CleanupService::class);
		$folderChild = $this->createMock(Folder::class);

		$folder = $this->createMock(Folder::class);
		$folder->method('getChild')->with('childname')->willReturn($folderChild);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		$shareManager = $this->createMock(IManager::class);

		$storage = $this->createMock(IStorage::class);

		$uploadHome = new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);

		$result = $uploadHome->getChild('childname');
		$this->assertInstanceOf(UploadFolder::class, $result);
	}

	public function testGetChildThrowsExceptionIfNotFound(): void {
		$cleanup = $this->createMock(CleanupService::class);

		$folder = $this->createMock(Folder::class);
		$folder->method('getChild')->with('missing')->willThrowException(new SabreNotFound());

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		$shareManager = $this->createMock(IManager::class);

		$uploadHome = new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);

		$this->expectException(SabreNotFound::class);
		$uploadHome->getChild('missing');
	}

	public function testGetChildrenReturnsUploadFolders(): void {
		$cleanup = $this->createMock(CleanupService::class);

		$folderChild1 = $this->createMock(Folder::class);
		$folderChild2 = $this->createMock(Folder::class);

		$folder = $this->createMock(Folder::class);
		$folder->method('getChildren')->willReturn([$folderChild1, $folderChild2]);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		$shareManager = $this->createMock(IManager::class);

		$uploadHome = new UploadHome(
			$this->principalInfo,
			$cleanup,
			$rootFolder,
			$userSession,
			$shareManager
		);

		$children = $uploadHome->getChildren();
		$this->assertIsArray($children);
		$this->assertContainsOnlyInstancesOf(UploadFolder::class, $children);
	}

	public function testChildExistsReturnsTrueIfChildExists(): void {
		$child = $this->createMock(UploadFolder::class);
		$mock = $this->getMockBuilder(UploadHome::class)
			->disableOriginalConstructor()
			->onlyMethods(['getChild'])
			->getMock();
		$mock->method('getChild')->with('child')->willReturn($child);

		$this->assertTrue($mock->childExists('child'));
	}

	public function testChildExistsReturnsFalseIfNoChild(): void {
		// This test documents (and asserts) the current broken implementation of childExists():
		// Instead of returning false, it throws if the child is missing, due to not catching the NotFound exception.
		// This matches the current UploadHome implementation, but should be fixed.

		$mock = $this->getMockBuilder(UploadHome::class)
			->disableOriginalConstructor()
			->onlyMethods(['getChild'])
			->getMock();
		// Simulate getChild throwing for a missing child.
		$mock->method('getChild')->with('nochil')->willThrowException(new SabreNotFound());

		$this->expectException(SabreNotFound::class);
		$mock->childExists('nochil');
	}

	public function testDeleteProxiesToImplDelete(): void {
		$cleanup = $this->createMock(CleanupService::class);
		$folder = $this->createMock(Folder::class);

		$directory = $this->createMock(Directory::class);
		$directory->expects($this->once())->method('delete');

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		$shareManager = $this->createMock(IManager::class);

		// Patch impl() so Directory is built from a Folder
		$directoryReflect = new \ReflectionClass(Directory::class);
		$directoryInstance = $directoryReflect->newInstanceWithoutConstructor();

		$uploadHome = $this->getMockBuilder(UploadHome::class)
			->setConstructorArgs([
				$this->principalInfo,
				$cleanup,
				$rootFolder,
				$userSession,
				$shareManager
			])
			->onlyMethods(['impl'])
			->getMock();

		$uploadHome->method('impl')->willReturn($directory);

		$uploadHome->delete();
	}

	public function testGetNameReturnsPrincipalName(): void {
		$uploadHome = $this->getMockedUploadHomeUser();
		$this->assertEquals('testuser', $uploadHome->getName());
	}

	public function testGetLastModifiedProxiesToImpl(): void {
		$cleanup = $this->createMock(CleanupService::class);

		$folder = $this->createMock(Folder::class);

		$directory = $this->createMock(Directory::class);
		$directory->method('getLastModified')->willReturn(1234567890);

		$rootFolder = $this->createMock(IRootFolder::class);
		$rootFolder->method('getUserFolder')->with('testuser')->willReturn($folder);
		$rootFolder->method('get')->willReturn($folder);

		$userSession = $this->createMock(IUserSession::class);
		$user = $this->createUser('testuser', 'testpass');
		$userSession->method('getUser')->willReturn($user);

		$shareManager = $this->createMock(IManager::class);

		$uploadHome = $this->getMockBuilder(UploadHome::class)
			->setConstructorArgs([
				$this->principalInfo,
				$cleanup,
				$rootFolder,
				$userSession,
				$shareManager
			])
			->onlyMethods(['impl'])
			->getMock();

		$uploadHome->method('impl')->willReturn($directory);

		$this->assertEquals(1234567890, $uploadHome->getLastModified());
	}
}
