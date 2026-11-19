<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WorkflowEngine\Tests\BackgroundJobs;

use OCA\WorkflowEngine\BackgroundJobs\Rotate;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RotateTest extends TestCase {
	private string $logFile;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logFile = sys_get_temp_dir() . '/workflowengine-rotate-' . bin2hex(random_bytes(8));
	}

	#[\Override]
	protected function tearDown(): void {
		@unlink($this->logFile);
		@unlink($this->logFile . '.1');

		parent::tearDown();
	}

	private function createJob(string $logFile, LoggerInterface $logger): Rotate {
		$appConfig = $this->createMock(IAppConfig::class);
		$appConfig->method('getAppValueString')
			->willReturn($logFile);

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')
			->willReturnCallback(static fn (string $key, string $default = ''): string => sys_get_temp_dir());
		$config->method('getSystemValueInt')
			->with('log_rotate_size', $this->anything())
			->willReturn(5);

		return new Rotate(
			$this->createMock(ITimeFactory::class),
			$appConfig,
			$config,
			$logger,
		);
	}

	public function testRotatesFileAtConfiguredSize(): void {
		file_put_contents($this->logFile, '12345');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('info')
			->with(
				'Log file "{filePath}" reached the configured rotation size of {maxSize} bytes and was moved to "{rotatedFile}"',
				[
					'app' => Rotate::class,
					'filePath' => $this->logFile,
					'maxSize' => 5,
					'rotatedFile' => $this->logFile . '.1',
				],
			);

		$job = $this->createJob($this->logFile, $logger);
		self::invokePrivate($job, 'run', [null]);

		$this->assertFileDoesNotExist($this->logFile);
		$this->assertSame('12345', file_get_contents($this->logFile . '.1'));
	}

	public function testDoesNotRotateFileBelowConfiguredSize(): void {
		file_put_contents($this->logFile, '1234');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');

		$job = $this->createJob($this->logFile, $logger);
		self::invokePrivate($job, 'run', [null]);

		$this->assertSame('1234', file_get_contents($this->logFile));
		$this->assertFileDoesNotExist($this->logFile . '.1');
	}

	public function testDoesNotRotateWhenLogFilePathIsEmpty(): void {
		file_put_contents($this->logFile, '12345');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');

		$job = $this->createJob('', $logger);
		self::invokePrivate($job, 'run', [null]);

		$this->assertSame('12345', file_get_contents($this->logFile));
		$this->assertFileDoesNotExist($this->logFile . '.1');
	}
}
