<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Trashbin;
use OCA\AdminAudit\IAuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class TrashbinTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Trashbin $trashbin;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->trashbin = new Trashbin($this->logger);
	}

	public function testDelete(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('File "/a.txt.d1700000000" deleted from trash bin.', ['app' => 'admin_audit', 'operation' => 'trashbin.file.deleted']);

		$this->trashbin->delete(['path' => '/a.txt.d1700000000']);
	}

	public function testRestore(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('File "/a.txt" restored from trash bin.', ['app' => 'admin_audit', 'operation' => 'trashbin.file.restored']);

		$this->trashbin->restore(['filePath' => '/a.txt', 'trashPath' => '/a.txt.d1700000000']);
	}
}
