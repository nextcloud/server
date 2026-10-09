<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests;

use OCA\AdminAudit\AuditLogger;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\Log\ILogFactory;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AuditLoggerTest extends TestCase {
	public function testUsesStructuredContextLogger(): void {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')
			->willReturnCallback(static fn (string $key, string $default): string => $key === 'logfile_audit' ? '/var/log/nextcloud-audit.log' : $default);

		$parentLogger = $this->createMock(LoggerInterface::class);
		$logFactory = $this->createMock(ILogFactory::class);
		$logFactory->expects($this->once())
			->method('getCustomPsrLogger')
			->with('/var/log/nextcloud-audit.log', 'file', 'Nextcloud', true)
			->willReturn($parentLogger);

		$parentLogger->expects($this->once())
			->method('info')
			->with('File deleted', ['app' => 'admin_audit', 'params' => ['path' => '/a.txt']]);

		$auditLogger = new AuditLogger($logFactory, $this->createMock(IAppConfig::class), $config);
		$auditLogger->info('File deleted', ['app' => 'admin_audit', 'params' => ['path' => '/a.txt']]);
	}
}
