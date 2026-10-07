<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\FileEventListener;
use OCA\Files_Versions\Events\VersionRestoredEvent;
use OCA\Files_Versions\Versions\IVersion;
use OCP\Files\File;
use OCP\Preview\BeforePreviewFetchedEvent;
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

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Preview accessed: (id: "42", width: "64", height: "64" crop: "1", mode: "fill", path: "/alice/files/a.jpg")',
				['app' => 'admin_audit', 'operation' => 'files.preview.accessed', 'params' => ['id' => 42, 'width' => 64, 'height' => 64, 'crop' => true, 'mode' => 'fill', 'path' => '/alice/files/a.jpg']],
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
