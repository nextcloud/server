<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Action;
use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Operation;
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

	public function testLogAddsOperationToContext(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('File "/a.txt" deleted', ['app' => 'admin_audit', 'operation' => 'files.file.deleted']);

		$this->action->log(Operation::FileDeleted, 'File "%s" deleted', ['path' => '/a.txt'], ['path']);
	}

	public function testLogAcceptsOperationString(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('Share "42" accepted', ['app' => 'admin_audit', 'operation' => 'federatedfilesharing.share.accepted']);

		$this->action->log('federatedfilesharing.share.accepted', 'Share "%s" accepted', ['id' => '42'], ['id']);
	}

	public function testLogWithoutOperation(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with('File "/a.txt" deleted', ['app' => 'admin_audit']);

		$this->action->log(null, 'File "%s" deleted', ['path' => '/a.txt'], ['path']);
	}

	public function testLogWithOperationAndNamedPlaceholders(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				'File "{path}" deleted',
				['app' => 'admin_audit', 'operation' => 'files.file.deleted', 'path' => '/a.txt'],
			);

		$this->action->log(Operation::FileDeleted, 'File "{path}" deleted', ['path' => '/a.txt'], ['path']);
	}

	public function testMissingParameterKeepsOperation(): void {
		$this->logger->expects($this->never())
			->method('info');
		$this->logger->expects($this->once())
			->method('critical')
			->with(
				'$params["path"] was missing. Transferred value: {params}',
				['app' => 'admin_audit', 'operation' => 'files.file.deleted', 'params' => ['id' => 42]],
			);

		$this->action->log(Operation::FileDeleted, 'File "%s" deleted', ['id' => 42], ['path']);
	}

	public function testMissingParameterObfuscatedKeepsOperation(): void {
		$this->logger->expects($this->once())
			->method('critical')
			->with(
				'$params["uid"] was missing.',
				['app' => 'admin_audit', 'operation' => 'auth.login.failed'],
			);

		$this->action->log(Operation::LoginFailed, 'Login failed: "%s"', [], ['uid'], true);
	}
}
