<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Files;
use OCA\AdminAudit\IAuditLogger;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\Events\Node\BeforeNodeReadEvent;
use OCP\Files\Events\Node\NodeCopiedEvent;
use OCP\Files\Events\Node\NodeCreatedEvent;
use OCP\Files\Events\Node\NodeRenamedEvent;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\File;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FilesTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Files $files;
	private File&MockObject $source;
	private File&MockObject $target;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->files = new Files($this->logger);

		$this->source = $this->createMock(File::class);
		$this->source->method('getId')->willReturn(41);
		$this->source->method('getPath')->willReturn('/alice/files/a.txt');
		$this->target = $this->createMock(File::class);
		$this->target->method('getId')->willReturn(42);
		$this->target->method('getPath')->willReturn('/alice/files/b.txt');
	}

	private function expectInfo(string $message, string $operation, array $params): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation, 'params' => $params]);
	}

	public function testRead(): void {
		$this->expectInfo('File with id "42" accessed: "/alice/files/b.txt"', 'files.file.read', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->read(new BeforeNodeReadEvent($this->target));
	}

	public function testRename(): void {
		$this->expectInfo('File renamed with id "42" from "/alice/files/a.txt" to "/alice/files/b.txt"', 'files.file.renamed', ['newid' => 42, 'oldpath' => '/alice/files/a.txt', 'newpath' => '/alice/files/b.txt']);
		$this->files->afterRename(new NodeRenamedEvent($this->source, $this->target));
	}

	public function testCreate(): void {
		$this->expectInfo('File with id "42" created: "/alice/files/b.txt"', 'files.file.created', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->create(new NodeCreatedEvent($this->target));
	}

	public function testCopy(): void {
		$this->expectInfo('File id copied from: "41" to "42", path from "/alice/files/a.txt" to "/alice/files/b.txt"', 'files.file.copied', ['oldid' => 41, 'newid' => 42, 'oldpath' => '/alice/files/a.txt', 'newpath' => '/alice/files/b.txt']);
		$this->files->copy(new NodeCopiedEvent($this->source, $this->target));
	}

	public function testWrite(): void {
		$this->expectInfo('File with id "42" written to: "/alice/files/b.txt"', 'files.file.written', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->write(new NodeWrittenEvent($this->target));
	}

	public function testDelete(): void {
		$this->expectInfo('File with id "42" deleted: "/alice/files/b.txt"', 'files.file.deleted', ['id' => 42, 'path' => '/alice/files/b.txt']);
		$this->files->delete(new BeforeNodeDeletedEvent($this->target));
	}
}
