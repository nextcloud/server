<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OC\Files\Storage\Wrapper\Jail;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\FileEventListener;
use OCA\Files_Sharing\SharedStorage;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCA\Files_Versions\Versions\IVersion;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IHomeStorage;
use OCP\Files\Storage\ISharedStorage;
use OCP\Preview\BeforePreviewFetchedEvent;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FileEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private FileEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new FileEventListener($this->logger);
	}

	public function testPreviewAccessed(): void {
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getPath')->willReturn('/alice/files/a.jpg');
		$file->method('getInternalPath')->willReturn('files/a.jpg');
		$storage = $this->createMock(IHomeStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === IHomeStorage::class);
		$file->method('getStorage')->willReturn($storage);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Preview accessed: (id: "42", width: "64", height: "64" crop: "1", mode: "fill", path: "/alice/files/a.jpg")',
				['app' => 'admin_audit', 'operation' => 'files.preview.accessed', 'params' => ['id' => 42, 'width' => 64, 'height' => 64, 'crop' => true, 'mode' => 'fill', 'path' => '/alice/files/a.jpg']],
			);

		$this->listener->handle(new BeforePreviewFetchedEvent($file, 64, 64, true, 'fill'));
	}

	public function testPreviewAccessedThroughShare(): void {
		$shareNode = $this->createMock(Folder::class);
		$shareNode->method('getPath')->willReturn('/bob/files/GROUPFOLDER1/photos');
		$share = $this->createMock(IShare::class);
		$share->method('getNode')->willReturn($shareNode);
		$home = $this->createMock(IHomeStorage::class);
		$home->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => $class === IHomeStorage::class);
		$storage = $this->createMock(SharedStorage::class);
		$storage->method('instanceOfStorage')->willReturnCallback(static fn (string $class): bool => in_array($class, [ISharedStorage::class, Jail::class], true));
		$storage->method('getShare')->willReturn($share);
		$storage->method('getUnjailedPath')->willReturnCallback(static fn (string $path): string => 'files/GROUPFOLDER1/photos/' . $path);
		$storage->method('getUnjailedStorage')->willReturn($home);
		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getPath')->willReturn('/alice/files/photos/a.jpg');
		$file->method('getInternalPath')->willReturn('a.jpg');
		$file->method('getStorage')->willReturn($storage);

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Preview accessed: (id: "42", width: "64", height: "64" crop: "1", mode: "fill", path: "/alice/files/photos/a.jpg")',
				['app' => 'admin_audit', 'operation' => 'files.preview.accessed', 'params' => ['id' => 42, 'width' => 64, 'height' => 64, 'crop' => true, 'mode' => 'fill', 'path' => '/alice/files/photos/a.jpg', 'ownerPath' => '/bob/files/GROUPFOLDER1/photos/a.jpg']],
			);

		$this->listener->handle(new BeforePreviewFetchedEvent($file, 64, 64, true, 'fill'));
	}

	public function testVersionRestored(): void {
		$version = $this->createMock(IVersion::class);
		$version->method('getRevisionId')->willReturn(1700000000);
		$version->method('getVersionPath')->willReturn('/a.txt');

		$this->logger->expects($this->once())
			->method('info')
			->with('Version "1700000000" of "/a.txt" was restored.', ['app' => 'admin_audit', 'operation' => 'versions.version.restored', 'params' => ['version' => 1700000000, 'path' => '/a.txt']]);

		$this->listener->handle(new VersionRestoredEvent($version));
	}
}
