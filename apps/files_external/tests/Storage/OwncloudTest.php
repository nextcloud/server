<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\OwnCloud;

/**
 * Class OwnCloudTest
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class OwncloudTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;

	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->loadConfig(__DIR__ . '/../config.php');
		$this->config['owncloud']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new OwnCloud($this->config['owncloud']);
		$this->instance->mkdir('/');
	}

	protected function shouldRunConfig(mixed $config): bool {
		return is_array($config) && ($config['owncloud']['run'] ?? false);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}
}
