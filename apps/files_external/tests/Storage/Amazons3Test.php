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
 * @group DB
 * @group S3
 *
 * @package OCA\Files_External\Tests\Storage
 */
class Amazons3Test extends \Test\Files\Storage\Storage {
	protected $config;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->config = include('files_external/tests/config.amazons3.php');
		if (!is_array($this->config) || !$this->config['run']) {
			$this->markTestSkipped('AmazonS3 backend not configured');
		}
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
}
