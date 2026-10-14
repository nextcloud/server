<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Versions;
use OCA\AdminAudit\IAuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class VersionsTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Versions $versions;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->versions = new Versions($this->logger);
	}

	public function testDelete(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('Version "/a.txt.v1700000000" was deleted.', ['app' => 'admin_audit', 'operation' => 'versions.version.deleted']);

		$this->versions->delete(['path' => '/a.txt.v1700000000']);
	}
}
