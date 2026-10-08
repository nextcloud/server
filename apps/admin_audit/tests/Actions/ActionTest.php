<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\IAuditLogger;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ActionTest extends TestCase {
	private IAuditLogger&MockObject $logger;
	private Action $action;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->action = new Action($this->logger);
	}

	public function testFormatsDateTimeParameter(): void {
		$date = new \DateTimeImmutable('2024-02-03T04:05:06+00:00');

		$this->logger->expects($this->once())
			->method('info')
			->with('Action occurred at 2024-02-03 04:05:06', ['app' => 'admin_audit']);

		$this->action->log('Action occurred at %s', ['date' => $date], ['date']);
	}

	public function testMissingParameterLogsCriticalWithParameters(): void {
		$params = ['provided' => 'value'];

		$this->logger->expects($this->once())
			->method('critical')
			->with(
				'$params["missing"] was missing. Transferred value: {params}',
				['app' => 'admin_audit', 'params' => $params],
			);
		$this->logger->expects($this->never())->method('info');

		$this->action->log('Action for %s', $params, ['missing']);
	}

	public function testMissingParameterOmitsParametersWhenObfuscationIsEnabled(): void {
		$this->logger->expects($this->once())
			->method('critical')
			->with(
				'$params["missing"] was missing.',
				['app' => 'admin_audit'],
			);
		$this->logger->expects($this->never())->method('info');

		$this->action->log(
			'Action for %s',
			['provided' => 'sensitive value'],
			['missing'],
			true,
		);
	}
}
