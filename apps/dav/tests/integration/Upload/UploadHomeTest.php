<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Tests\integration\Upload;

use OC\Files\Filesystem;
use OC\Files\Storage\Temporary;
use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\UploadHome;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use OCP\Share\IManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[Group('DB')]
class UploadHomeTest extends TestCase {
	private string $uid;
	private ?Temporary $storage = null;
	private IRootFolder $rootFolder;
	private CleanupService&MockObject $cleanup;
	private UploadHome $home;

	protected function setUp(): void {
		parent::setUp();

		$this->uid = $this->getUniqueID('uploadhome_');
		$this->storage = new Temporary([]);
		$this->storage->mkdir('uploads');

		Filesystem::initInternal('/');
		Filesystem::mount($this->storage, [], '/' . $this->uid);
		$this->storage->getScanner()->scan('');

		$this->rootFolder = Server::get(IRootFolder::class);
		$this->cleanup = $this->createMock(CleanupService::class);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($this->uid);
		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn($user);

		$this->home = new UploadHome(
			['uri' => 'principals/users/' . $this->uid],
			$this->cleanup,
			$this->rootFolder,
			$userSession,
			$this->createMock(IManager::class),
		);
	}

	protected function tearDown(): void {
		try {
			if ($this->storage !== null) {
				$this->storage->getCache()->clear();
				$this->storage->cleanUp();
			}
		} finally {
			$this->logout();
			parent::tearDown();
		}
	}

	public function testCreateDirectorySchedulesCleanup(): void {
		$this->cleanup->expects($this->once())
			->method('addJob')
			->with($this->uid, 'upload-1');

		$this->home->createDirectory('upload-1');

		$this->assertInstanceOf(
			Folder::class,
			$this->rootFolder->get('/' . $this->uid . '/uploads/upload-1'),
		);
	}

	public function testMissingChildDoesNotExist(): void {
		$this->assertFalse($this->home->childExists('missing'));
	}
}
