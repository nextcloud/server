<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Sharing;
use OCA\AdminAudit\IAuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SharingTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Sharing $sharing;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->sharing = new Sharing($this->logger);
	}

	private function expectInfo(string $message, string $operation, array $params): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation, 'params' => $params]);
	}

	public function testUpdatePermissions(): void {
		$this->expectInfo('The permissions of the shared file "/a.txt" with ID "42" have been changed to "1"', 'sharing.share.permissions_updated', ['itemType' => 'file', 'path' => '/a.txt', 'itemSource' => 42, 'permissions' => 1]);
		$this->sharing->updatePermissions([
			'itemType' => 'file',
			'path' => '/a.txt',
			'itemSource' => 42,
			'permissions' => 1,
		]);
	}

	public function testUpdatePassword(): void {
		$this->expectInfo('The password of the publicly shared file "abc" with ID "42" has been changed', 'sharing.share.password_updated', ['itemType' => 'file', 'token' => 'abc', 'itemSource' => 42]);
		$this->sharing->updatePassword([
			'itemType' => 'file',
			'token' => 'abc',
			'itemSource' => 42,
		]);
	}

	public function testExpirationDateRemoved(): void {
		$this->expectInfo('The expiration date of the publicly shared file with ID "42" has been removed', 'sharing.share.expiration_removed', ['itemType' => 'file', 'itemSource' => 42]);
		$this->sharing->updateExpirationDate([
			'itemType' => 'file',
			'itemSource' => 42,
			'date' => null,
		]);
	}

	public function testExpirationDateUpdated(): void {
		$this->expectInfo('The expiration date of the publicly shared file with ID "42" has been changed to "2026-10-31 00:00:00"', 'sharing.share.expiration_updated', ['itemType' => 'file', 'itemSource' => 42, 'date' => '2026-10-31 00:00:00']);
		$this->sharing->updateExpirationDate([
			'itemType' => 'file',
			'itemSource' => 42,
			'date' => new \DateTimeImmutable('2026-10-31 00:00:00'),
		]);
	}

	public function testShareAccessed(): void {
		$this->expectInfo('The shared file with the token "abc" by "alice" has been accessed.', 'sharing.share.link_accessed', ['itemType' => 'file', 'token' => 'abc', 'uidOwner' => 'alice']);
		$this->sharing->shareAccessed([
			'itemType' => 'file',
			'token' => 'abc',
			'uidOwner' => 'alice',
		]);
	}
}
