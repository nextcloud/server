<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
#[\PHPUnit\Framework\Attributes\Group('S3')]
class Amazons3MultiPartTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');

		$this->instance = new AmazonS3($this->config + [
			'putSizeLimit' => 1,
			'copySizeLimit' => 1,
		]);
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
}
