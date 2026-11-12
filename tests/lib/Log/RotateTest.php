<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\File;
use OC\Log\Rotate;
use OC\SystemConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\Log\ILogFactory;
use Test\TestCase;

class RotateTest extends TestCase {
	private string $dir;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->dir = sys_get_temp_dir() . '/' . self::getUniqueID('rotate');
		mkdir($this->dir);
	}

	#[\Override]
	protected function tearDown(): void {
		@unlink($this->dir . '/nextcloud.log');
		@unlink($this->dir . '/nextcloud.log.1');
		@rmdir($this->dir);
		parent::tearDown();
	}

	public function testRotatesFallbackLogFile(): void {
		$systemConfig = $this->createMock(SystemConfig::class);
		$systemConfig->method('getValue')->willReturnArgument(1);
		$logFile = new File($this->dir . '/missing/nextcloud.log', $this->dir . '/nextcloud.log', $systemConfig);
		file_put_contents($this->dir . '/nextcloud.log', 'log entry');

		$logFactory = $this->createMock(ILogFactory::class);
		$logFactory->method('get')->with('file')->willReturn($logFile);
		$this->overwriteService(ILogFactory::class, $logFactory);

		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueInt')->with('log_rotate_size')->willReturn(1);
		$this->overwriteService(IConfig::class, $config);

		$job = new Rotate($this->createMock(ITimeFactory::class));
		$job->run(null);

		$this->assertFileDoesNotExist($this->dir . '/nextcloud.log');
		$this->assertFileExists($this->dir . '/nextcloud.log.1');
	}
}
