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

	#[\Override]
	public function setUp():void {
		parent::setUp();

		$tempManager = $this->createMock(ITempManager::class);
		$this->path = sys_get_temp_dir() . '/' . uniqid('file_sequence_test_');
		mkdir($this->path);
		$tempManager->method('getTempBaseDir')->willReturn($this->path);
		$this->sequence = new FileSequence($tempManager);
	}

	#[\Override]
	public function tearDown():void {
		foreach (glob($this->path . '/' . FileSequence::LOCK_FILE_DIRECTORY . '*/*') as $file) {
			unlink($file);
		}
		foreach (glob($this->path . '/' . FileSequence::LOCK_FILE_DIRECTORY . '*') as $directory) {
			rmdir($directory);
		}
		rmdir($this->path);

		parent::tearDown();
	}

	public function testSequenceIncrementsWithinTheSameMillisecond(): void {
		$this->assertSame(0, $this->sequence->nextId(42, 1000, 500));
		$this->assertSame(1, $this->sequence->nextId(42, 1000, 500));
		$this->assertSame(2, $this->sequence->nextId(42, 1000, 500));
	}

	public function testSequenceStartsAtZeroForEachMillisecond(): void {
		$this->assertSame(0, $this->sequence->nextId(42, 1000, 500));
		// Same lock file, different slot
		$this->assertSame(0, $this->sequence->nextId(42, 1000, 520));
		$this->assertSame(0, $this->sequence->nextId(42, 1001, 500));
		// Same slot, reused after the TTL window
		$this->assertSame(0, $this->sequence->nextId(42, 1030, 500));
	}

	public function testSequenceKeepsEarlierMillisecondsWithinTheWindow(): void {
		$this->assertSame(0, $this->sequence->nextId(42, 1000, 500));
		$this->assertSame(0, $this->sequence->nextId(42, 1001, 500));
		$this->assertSame(1, $this->sequence->nextId(42, 1000, 500));
	}
}
