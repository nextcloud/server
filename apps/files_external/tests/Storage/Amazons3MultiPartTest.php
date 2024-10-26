<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\files_external\tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class Amazons3MultiPartTest extends \Test\Files\Storage\Storage {
	private $config;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->config = include('files_external/tests/config.amazons3.php');
		if (! is_array($this->config) or ! $this->config['run']) {
			$this->markTestSkipped('AmazonS3 backend not configured');
		}
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

	public function testHashInFileName(): void {
		$this->markTestSkipped('Localstack has a bug with hashes in filename');
	}
}
