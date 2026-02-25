<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
#[\PHPUnit\Framework\Attributes\Group('S3')]
class Amazons3Test extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');
		$this->instance = new AmazonS3($this->config);
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

	/**
	 * Test rmdir() with a directory containing more than 1000 files to validate S3 batchDelete logic.
	 */
	public function testRmdirManyFiles() {
		$dir = 'bigDir';
		$numFiles = 1100; // S3 batchDelete limit is 1000

		// Create the directory
		$this->assertTrue($this->instance->mkdir($dir));

		// Create 1100 files inside the directory
		for ($i = 0; $i < $numFiles; $i++) {
			$filePath = $dir . '/file' . $i . '.txt';
			$this->assertNotFalse(
				$this->instance->file_put_contents($filePath, 'test content'),
				"Failed to create file: $filePath"
			);
		}

		$this->wait();
		// Confirm directory and files exist
		$this->assertTrue($this->instance->is_dir($dir));
		$this->assertTrue($this->instance->file_exists($dir . '/file0.txt'));
		$this->assertTrue($this->instance->file_exists($dir . '/file1099.txt'));

		// Delete the directory (should trigger multi-batchDelete in S3)
		$this->assertTrue(
			$this->instance->rmdir($dir),
			'rmdir failed on bigDir with many files'
		);

		$this->wait();
		// Confirm directory and files are deleted
		$this->assertFalse($this->instance->file_exists($dir));
		$this->assertFalse($this->instance->file_exists($dir . '/file0.txt'));
		$this->assertFalse($this->instance->file_exists($dir . '/file1099.txt'));
	}
}
