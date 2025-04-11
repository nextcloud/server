<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use GuzzleHttp\Exception\ClientException;
use OCA\Files_External\Lib\Storage\Swift;

/**
 * Class SwiftTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class SwiftTest extends \Test\Files\Storage\Storage {
	private $config;

	/**
	 * @var Swift instance
	 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->config = include('files_external/tests/config.swift.php');
		if (!is_array($this->config) or !$this->config['run']) {
			$this->markTestSkipped('OpenStack Object Storage backend not configured');
		}
		$this->instance = new Swift($this->config);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			try {
				$container = $this->instance->getContainer();

				$objects = $container->listObjects();
				foreach ($objects as $object) {
					$object->delete();
				}

				$container->delete();
			} catch (ClientException $e) {
				// container didn't exist, so we don't need to delete it
			}
		}

		parent::tearDown();
	}

	public function testStat(): void {
		$this->markTestSkipped('Swift doesn\'t update the parents folder mtime');
	}
}
