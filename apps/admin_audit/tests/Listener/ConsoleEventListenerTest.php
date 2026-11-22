<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\ConsoleEventListener;
use OCP\Console\ConsoleEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ConsoleEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private ConsoleEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new ConsoleEventListener($this->logger);
	}

	public function testCommandExecuted(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('Console command executed: user:disable carol', ['app' => 'admin_audit', 'operation' => 'console.command.executed', 'params' => ['arguments' => 'user:disable carol']]);

		$this->listener->handle(new ConsoleEvent(ConsoleEvent::EVENT_RUN, ['occ', 'user:disable', 'carol']));
	}
}
