<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\AllConfig;
use OCP\IConfig;

/**
 * Class PartFileInRootUploadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre\RequestTest
 */
class PartFileInRootUploadTest extends UploadTest {
	protected function setUp(): void {
		$config = \OC::$server->getConfig();
		$mockConfig = $this->getMockBuilder(IConfig::class)
			->disableOriginalConstructor()
			->getMock();
		$mockConfig->expects($this->any())
			->method('getSystemValue')
			->willReturnCallback(function ($key, $default) use ($config) {
				if ($key === 'part_file_in_storage') {
					return false;
				} else {
					return $config->getSystemValue($key, $default);
				}
			});
		$this->overwriteService(AllConfig::class, $mockConfig);
		parent::setUp();
	}

	protected function tearDown(): void {
		$this->restoreService('AllConfig');
		parent::tearDown();
	}
}
