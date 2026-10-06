<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Log;

use OC\Log\Rotate;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Server;
use Test\TestCase;

class RotateTest extends TestCase {
	private string $logFile;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->logFile = sys_get_temp_dir() . '/nextcloud-log-rotate-' . bin2hex(random_bytes(8));
		$this->overwriteSystemConfig('logfile', $this->logFile);
		$this->overwriteSystemConfig('log_rotate_size', 5);
	}

	#[\Override]
	protected function tearDown(): void {
		@unlink($this->logFile);
		@unlink($this->logFile . '.1');

		parent::tearDown();
	}

	public function testRotatesFileAtConfiguredSize(): void {
		file_put_contents($this->logFile, '12345');

		$job = new Rotate(Server::get(ITimeFactory::class));
		$job->run(null);

		$this->assertFileDoesNotExist($this->logFile);
		$this->assertSame('12345', file_get_contents($this->logFile . '.1'));
	}

	public function testDoesNotRotateFileBelowConfiguredSize(): void {
		file_put_contents($this->logFile, '1234');

		$job = new Rotate(Server::get(ITimeFactory::class));
		$job->run(null);

		$this->assertSame('1234', file_get_contents($this->logFile));
		$this->assertFileDoesNotExist($this->logFile . '.1');
	}
}
