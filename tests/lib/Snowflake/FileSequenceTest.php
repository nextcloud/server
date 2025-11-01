<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Snowflake;

use OC\Snowflake\FileSequence;
use OCP\ITempManager;

/**
 * @package Test
 */
class FileSequenceTest extends ISequenceBase {
	private string $path;

	public function setUp():void {
		$tempManager = $this->createMock(ITempManager::class);
		$this->path = uniqid(sys_get_temp_dir() . '/php_test_seq_', true);
		mkdir($this->path);
		$tempManager->method('getTemporaryFolder')->willReturn($this->path);
		$this->sequence = new FileSequence($tempManager);
	}

	public function tearDown():void {
		foreach (glob($this->path . '/*') as $file) {
			unlink($file);
		}
		rmdir($this->path);
	}
}
