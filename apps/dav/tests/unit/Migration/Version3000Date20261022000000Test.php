<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Migration;

use OCA\DAV\CardDAV\SyncService;
use OCA\DAV\Migration\Version3000Date20261022000000;
use OCP\IAppConfig;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;

class Version3000Date20261022000000Test extends TestCase {
	private SyncService&MockObject $syncService;
	private LoggerInterface&MockObject $logger;
	private IUserManager&MockObject $userManager;
	private IAppConfig&MockObject $config;
	private IOutput&MockObject $output;
	private Version3000Date20261022000000 $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->syncService = $this->createMock(SyncService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->config = $this->createMock(IAppConfig::class);
		$this->output = $this->createMock(IOutput::class);
		$this->migration = new Version3000Date20261022000000($this->syncService, $this->logger, $this->userManager, $this->config);
	}

	public function testRewritesTheSystemAddressBook(): void {
		$this->userManager->method('countSeenUsers')->willReturn(5);
		$this->userManager->method('countUsersTotal')->willReturn(5);

		$this->syncService->expects($this->once())->method('syncInstance');
		$this->config->expects($this->once())->method('setValueString')->with('dav', 'needs_system_address_book_sync', 'no');

		$this->migration->postSchemaChange($this->output, fn () => null, []);
	}

	public function testLeavesLargeInstancesToTheCommand(): void {
		$this->userManager->method('countSeenUsers')->willReturn(150);
		$this->userManager->method('countUsersTotal')->willReturn(150);

		$this->syncService->expects($this->never())->method('syncInstance');
		$this->config->expects($this->once())->method('setValueString')->with('dav', 'needs_system_address_book_sync', 'yes');
		$this->output->expects($this->once())->method('info');

		$this->migration->postSchemaChange($this->output, fn () => null, []);
	}

	public function testFlagsAFailedRewrite(): void {
		$this->userManager->method('countSeenUsers')->willReturn(5);
		$this->userManager->method('countUsersTotal')->willReturn(5);

		$this->syncService->method('syncInstance')->willThrowException(new RuntimeException('boom'));
		$this->config->expects($this->once())->method('setValueString')->with('dav', 'needs_system_address_book_sync', 'yes');
		$this->logger->expects($this->once())->method('error');
		$this->output->expects($this->once())->method('warning');

		$this->migration->postSchemaChange($this->output, fn () => null, []);
	}
}
