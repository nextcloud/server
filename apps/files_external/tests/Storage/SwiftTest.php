<?php

declare(strict_types=1);
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
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class SwiftTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;

	/**
	 * @var Swift instance
	 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.swift.php');
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
