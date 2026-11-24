<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\BackgroundJobs;

use OC\Core\BackgroundJobs\BackgroundCleanupUpdaterBackupsJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files;
use OCP\IConfig;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class BackgroundCleanupUpdaterBackupsJobTest extends TestCase {
	private IConfig&MockObject $config;
	private LoggerInterface&MockObject $logger;
	private BackgroundCleanupUpdaterBackupsJob $job;
	private string $updateDir;

	protected function setUp(): void {
		parent::setUp();

		$this->updateDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->willReturnMap([
				['updatedirectory', null, $this->updateDir],
				['instanceid', null, 'oc1234'],
			]);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->job = new BackgroundCleanupUpdaterBackupsJob(
			$this->config,
			$this->logger,
			$this->createMock(ITimeFactory::class),
		);
	}

	protected function tearDown(): void {
		Files::rmdirr($this->updateDir);
		parent::tearDown();
	}

	public function testMissingBackupFolderIsNotAWarning(): void {
		$this->logger->expects($this->never())->method('warning');
		$this->logger->expects($this->never())->method('error');
		$messages = [];
		$this->logger->method('info')
			->willReturnCallback(function (string $message) use (&$messages): void {
				$messages[] = $message;
			});

		$this->job->run([]);

		$this->assertStringContainsString('could not find updater backup folder', end($messages));
	}

	public function testKeepsNewestThreeBackups(): void {
		$backupDir = $this->updateDir . '/updater-oc1234/backups';
		for ($i = 1; $i <= 5; $i++) {
			mkdir("$backupDir/backup$i", 0777, true);
			touch("$backupDir/backup$i", 1000 * $i);
		}

		$this->logger->expects($this->never())->method('warning');
		$this->logger->expects($this->never())->method('error');

		$this->job->run([]);

		$this->assertDirectoryDoesNotExist("$backupDir/backup1");
		$this->assertDirectoryDoesNotExist("$backupDir/backup2");
		$this->assertDirectoryExists("$backupDir/backup3");
		$this->assertDirectoryExists("$backupDir/backup4");
		$this->assertDirectoryExists("$backupDir/backup5");
	}
}
