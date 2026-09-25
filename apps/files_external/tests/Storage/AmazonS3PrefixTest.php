<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Runs the full storage test suite against an S3 mount that has a prefix set,
 * and adds isolation tests to verify that two mounts with different prefixes
 * in the same bucket cannot see each other's files.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
#[\PHPUnit\Framework\Attributes\Group('S3')]
class Amazons3PrefixTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;

	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');
		$this->instance = new AmazonS3($this->config + ['prefix' => 'test-prefix-a/']);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	public function testStat(): void {
		$this->markTestSkipped('S3 doesn\'t update the parents folder mtime');
	}

	public function testPrefixIsolation(): void {
		$storageA = new AmazonS3($this->config + ['prefix' => 'test-prefix-a/']);
		$storageB = new AmazonS3($this->config + ['prefix' => 'test-prefix-b/']);

		try {
			$storageA->file_put_contents('hello.txt', 'from-a');

			// B must not see a file written by A
			$this->assertFalse($storageB->file_exists('hello.txt'), 'Storage B must not see files written by Storage A');

			$storageB->file_put_contents('hello.txt', 'from-b');

			// Each storage reads its own copy
			$this->assertSame('from-a', $storageA->file_get_contents('hello.txt'));
			$this->assertSame('from-b', $storageB->file_get_contents('hello.txt'));
		} finally {
			$storageA->rmdir('');
			$storageB->rmdir('');
		}
	}

	public function testPrefixIsolationDirectory(): void {
		$storageA = new AmazonS3($this->config + ['prefix' => 'test-prefix-a/']);
		$storageB = new AmazonS3($this->config + ['prefix' => 'test-prefix-b/']);

		try {
			$storageA->mkdir('subdir');
			$storageA->file_put_contents('subdir/file.txt', 'data');

			$this->assertFalse($storageB->is_dir('subdir'), 'Storage B must not see directories created by Storage A');
			$this->assertFalse($storageB->file_exists('subdir/file.txt'), 'Storage B must not see files in directories created by Storage A');
		} finally {
			$storageA->rmdir('');
			$storageB->rmdir('');
		}
	}

	public function testNoPrefixAndPrefixedMountDoNotOverlap(): void {
		$withPrefix = new AmazonS3($this->config + ['prefix' => 'test-prefix-a/']);
		$withoutPrefix = new AmazonS3($this->config);

		try {
			$withPrefix->file_put_contents('scoped.txt', 'scoped');

			// The un-prefixed mount must not see 'scoped.txt' at its root
			$this->assertFalse($withoutPrefix->file_exists('scoped.txt'), 'Un-prefixed mount must not see files from prefixed mount at its root');
		} finally {
			$withPrefix->rmdir('');
		}
	}
}
