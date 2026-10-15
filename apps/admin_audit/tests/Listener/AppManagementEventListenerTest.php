<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\AppManagementEventListener;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\App\Events\AppUpdateEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppManagementEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private AppManagementEventListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new AppManagementEventListener($this->logger);
	}

	private function expectInfo(string $message, string $operation): void {
		$this->logger->expects($this->once())
			->method('info')
			->with($message, ['app' => 'admin_audit', 'operation' => $operation]);
	}

	public function testAppEnabled(): void {
		$this->expectInfo('App "files" enabled', 'apps.app.enabled');
		$this->listener->handle(new AppEnableEvent('files'));
	}

	public function testAppEnabledForGroups(): void {
		$this->expectInfo('App "files" enabled for groups: admin, staff', 'apps.app.enabled');
		$this->listener->handle(new AppEnableEvent('files', ['admin', 'staff']));
	}

	public function testAppDisabled(): void {
		$this->expectInfo('App "files" disabled', 'apps.app.disabled');
		$this->listener->handle(new AppDisableEvent('files'));
	}

	public function testAppUpdated(): void {
		$this->expectInfo('App "files" updated', 'apps.app.updated');
		$this->listener->handle(new AppUpdateEvent('files'));
	}
}
