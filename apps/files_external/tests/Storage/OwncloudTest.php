<?php
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
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class OwncloudTest extends \Test\Files\Storage\Storage {
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.php');
		if (! is_array($this->config) or ! isset($this->config['owncloud']) or ! $this->config['owncloud']['run']) {
			$this->markTestSkipped('Nextcloud backend not configured');
		}
		$this->config['owncloud']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new OwnCloud($this->config['owncloud']);
		$this->instance->mkdir('/');
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}
}
