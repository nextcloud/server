<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\unit\Upload;

use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Test\TestCase;

class UploadHomeTest extends TestCase {
	private CleanupService&MockObject $cleanup;
	private IRootFolder&MockObject $rootFolder;
	private IUserSession&MockObject $userSession;
	private IManager&MockObject $shareManager;

	protected function setUp(): void {
		parent::setUp();

		$this->cleanup = $this->createMock(CleanupService::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->shareManager = $this->createMock(IManager::class);
	}

	private function home(string $principal): UploadHome {
		return new UploadHome(
			['uri' => $principal],
			$this->cleanup,
			$this->rootFolder,
			$this->userSession,
			$this->shareManager,
		);
	}

	private function useUser(string $uid): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);
		$this->userSession->method('getUser')->willReturn($user);
	}

	private function expectUploadFolder(string $uid): Folder&MockObject {
		$path = '/' . $uid . '/uploads';
		$folder = $this->createMock(Folder::class);
		$folder->method('getPath')->willReturn($path);
		$this->rootFolder->expects($this->once())
			->method('get')
			->with($path)
			->willReturn($folder);

		return $folder;
	}

	public function testUserUsesSessionUserUploadFolder(): void {
		$this->useUser('alice');
		$folder = $this->expectUploadFolder('alice');
		$folder->method('getMtime')->willReturn(1234);

		$home = $this->home('principals/users/alice');

		$this->assertSame(1234, $home->getLastModified());
	}

	public function testShareUsesOwnerUploadFolder(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn('owner');
		$this->shareManager->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);
		$this->userSession->expects($this->never())->method('getUser');

		$folder = $this->expectUploadFolder('owner');
		$folder->method('getMtime')->willReturn(1234);

		$home = $this->home('principals/shares/token');

		$this->assertSame(1234, $home->getLastModified());
		$this->assertSame('token', $home->getName());
	}

	public function testUserRequiresSession(): void {
		$this->userSession->method('getUser')->willReturn(null);

		$this->expectException(Forbidden::class);
		$this->home('principals/users/alice');
	}

	public function testCreateFileIsForbidden(): void {
		$this->useUser('alice');
		$home = $this->home('principals/users/alice');

		$this->expectException(Forbidden::class);
		$home->createFile('file');
	}

	public function testRenameIsForbidden(): void {
		$this->useUser('alice');
		$home = $this->home('principals/users/alice');

		$this->expectException(Forbidden::class);
		$home->setName('new-name');
	}

	public function testListingIsForbidden(): void {
		$this->useUser('alice');
		$home = $this->home('principals/users/alice');

		$this->expectException(MethodNotAllowed::class);
		$home->getChildren();
	}

	public function testFailedDirectoryCreationDoesNotScheduleCleanup(): void {
		$this->useUser('alice');
		$folder = $this->expectUploadFolder('alice');
		$folder->method('isCreatable')->willReturn(false);
		$this->cleanup->expects($this->never())->method('addJob');
		$home = $this->home('principals/users/alice');

		$this->expectException(Forbidden::class);
		$home->createDirectory('upload');
	}

	public function testInaccessibleChildDoesNotExist(): void {
		$this->useUser('alice');
		$folder = $this->expectUploadFolder('alice');
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')->willReturn(false);
		$folder->method('getStorage')->willReturn($storage);
		$folder->method('isReadable')->willReturn(false);
		$home = $this->home('principals/users/alice');

		$this->assertFalse($home->childExists('upload'));
	}
}
