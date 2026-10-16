<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\Rotate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RotateTest extends TestCase {
	private string $logFile;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logFile = sys_get_temp_dir() . '/nextcloud-log-rotate-' . bin2hex(random_bytes(8));
	}

	#[\Override]
	protected function tearDown(): void {
		@unlink($this->logFile);
		@unlink($this->logFile . '.1/keep');
		@rmdir($this->logFile . '.1');
		@unlink($this->logFile . '.1');

		parent::tearDown();
	}

	private function createJob(IConfig $config, LoggerInterface $logger): Rotate {
		return new Rotate(
			$this->createMock(ITimeFactory::class),
			$config,
			$logger,
		);
	}

	private function createConfig(string $logFile): IConfig {
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueString')
			->willReturnCallback(static function (string $key, string $default = '') use ($logFile): string {
				return match ($key) {
					'datadirectory' => sys_get_temp_dir(),
					'logfile' => $logFile,
					default => $default,
				};
			});
		$config->method('getSystemValueInt')
			->with('log_rotate_size', $this->anything())
			->willReturn(5);

		return $config;
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

		$job = $this->createJob($this->createConfig($this->logFile), $logger);
		$job->run(null);

		$this->assertFileDoesNotExist($this->logFile);
		$this->assertSame('12345', file_get_contents($this->logFile . '.1'));
	}

	public function testDoesNotRotateFileBelowConfiguredSize(): void {
		file_put_contents($this->logFile, '1234');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');

		$job = $this->createJob($this->createConfig($this->logFile), $logger);
		$job->run(null);

		$this->assertSame('1234', file_get_contents($this->logFile));
		$this->assertFileDoesNotExist($this->logFile . '.1');
	}

	public function testDoesNotRotateWhenLogFilePathIsEmpty(): void {
		file_put_contents($this->logFile, '12345');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');

		$job = $this->createJob($this->createConfig(''), $logger);
		$job->run(null);

		$this->assertSame('12345', file_get_contents($this->logFile));
		$this->assertFileDoesNotExist($this->logFile . '.1');
	}

	public function testThrowsWhenRotatedFileCannotBeCreated(): void {
		file_put_contents($this->logFile, '12345');
		mkdir($this->logFile . '.1');
		file_put_contents($this->logFile . '.1/keep', 'keep');

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->never())->method('info');

		$job = $this->createJob($this->createConfig($this->logFile), $logger);

		try {
			$job->run(null);
			self::fail('Expected rotation to fail when the destination is a directory.');
		} catch (\RuntimeException $e) {
			$this->assertSame(
				sprintf('Failed to rotate log file "%s" to "%s".', $this->logFile, $this->logFile . '.1'),
				$e->getMessage(),
			);
		}

		$this->assertSame('12345', file_get_contents($this->logFile));
		$this->assertSame('keep', file_get_contents($this->logFile . '.1/keep'));
	}
}
