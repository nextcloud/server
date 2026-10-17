<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\FederationEventListener;
use OCP\Federation\Events\TrustedServerRemovedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class FederationEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private FederationEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new FederationEventListener($this->logger);
	}

	public function testTrustedServerRemoved(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Trusted server "https://remote.example.com" was removed',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'federation.trusted_server.removed'),
			);

		$this->listener->handle(new TrustedServerRemovedEvent('hash', 'https://remote.example.com'));
	}
}
