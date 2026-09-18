<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OC\Files\Cache\Scanner;
use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
#[\PHPUnit\Framework\Attributes\Group(name: 'S3')]
class Amazons3MultiPartTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;
	use CrossMountS3ConfigTrait;

	// Above the peer's 1-byte copy threshold: forces the MultipartCopy branch on every object.
	private const MPU_TRIGGER_BYTES = 6 * 1024 * 1024;

	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');
		if ($this->config['run_cross_mount'] ?? false) {
			$this->enableServerSideCopyFlagBeforeInstances();
		}

		// putSizeLimit / copySizeLimit = 1 forces every single-object PUT and every copy to take
		// the MultipartUploader / MultipartCopy branch.
		$this->instance = new AmazonS3($this->config + [
			'putSizeLimit' => 1,
			'copySizeLimit' => 1,
		]);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}
		AmazonS3::resetServerSideCopyFailureCounter();
		$this->restoreServerSideCopyFlag();

		parent::tearDown();
	}

	public function testStat(): void {
		$this->markTestSkipped('S3 doesn\'t update the parents folder mtime');
	}

	public function testCrossMountLargeFileTriggersMultipartCopy(): void {
		$peer = $this->newPeerStorage([
			'putSizeLimit' => 1,
			'copySizeLimit' => 1,
		]);
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);

			$payload = str_repeat('x', self::MPU_TRIGGER_BYTES);
			$this->instance->file_put_contents('mpu-cross.bin', $payload);
			$this->instance->getScanner()->scanFile('mpu-cross.bin');

			$result = $peer->moveFromStorage($this->instance, 'mpu-cross.bin', 'mpu-cross.bin');
			$this->assertTrue($result);
			$this->assertFalse($this->instance->file_exists('mpu-cross.bin'), 'source must be deleted after MPU-forcing move');
			$this->assertSame($payload, $peer->file_get_contents('mpu-cross.bin'));
		} finally {
			$peer->unlink('mpu-cross.bin');
			$this->instance->unlink('mpu-cross.bin');
		}
	}
}
