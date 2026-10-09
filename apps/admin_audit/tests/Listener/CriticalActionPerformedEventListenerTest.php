<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\CriticalActionPerformedEventListener;
use OCP\EventDispatcher\Event;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CriticalActionPerformedEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private CriticalActionPerformedEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new CriticalActionPerformedEventListener($this->logger);
	}

	public function testSprintfPlaceholders(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('Share "42" was accepted by "alice"', ['app' => 'admin_audit', 'params' => ['42', 'alice']]);

		$this->listener->handle(new CriticalActionPerformedEvent('Share "%s" was accepted by "%s"', ['42', 'alice']));
	}

	public function testNamedPlaceholders(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Bruteforce attempt from "{ip}" detected for action "{action}" not throttled due to allow-list.',
				['app' => 'admin_audit', 'ip' => '10.0.0.1', 'action' => 'login', 'params' => ['ip' => '10.0.0.1', 'action' => 'login']],
			);

		$this->listener->handle(new CriticalActionPerformedEvent(
			'Bruteforce attempt from "{ip}" detected for action "{action}" not throttled due to allow-list.',
			['ip' => '10.0.0.1', 'action' => 'login'],
		));
	}

	public function testUnrelatedEventIsIgnored(): void {
		$this->logger->expects($this->never())
			->method($this->anything());

		$this->listener->handle(new Event());
	}

	public function testOperationIsPassedThrough(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Federated share with id "42" was accepted',
				['app' => 'admin_audit', 'operation' => 'federatedfilesharing.share.accepted', 'params' => ['42']],
			);

		$this->listener->handle(new CriticalActionPerformedEvent(
			'Federated share with id "%s" was accepted',
			['42'],
			false,
			'federatedfilesharing.share.accepted',
		));
	}
}
