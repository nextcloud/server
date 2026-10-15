<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\CacheEventListener;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryRemovedEvent;
use OCP\Files\Storage\IStorage;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CacheEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private CacheEventListener $listener;
	private IStorage&MockObject $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new CacheEventListener($this->logger);
		$this->storage = $this->createMock(IStorage::class);
	}

	private function expectInfo(string $message, string $operation): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation]);
	}

	public function testEntryInserted(): void {
		$this->expectInfo('Cache entry inserted for fileid "42", path "files/a.txt" on storageid "3"', 'files.cache_entry.inserted');
		$this->listener->handle(new CacheEntryInsertedEvent($this->storage, 'files/a.txt', 42, 3));
	}

	public function testEntryRemoved(): void {
		$this->expectInfo('Cache entry removed for fileid "42", path "files/a.txt" on storageid "3"', 'files.cache_entry.removed');
		$this->listener->handle(new CacheEntryRemovedEvent($this->storage, 'files/a.txt', 42, 3));
	}
}
