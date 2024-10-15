<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\Storage\OwnCloud;

/**
 * Class OwnCloudFunctions
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests
 */
class OwnCloudFunctionsTest extends \Test\TestCase {
	public function configUrlProvider() {
		return [
			[
				[
					'host' => 'testhost',
					'root' => 'testroot',
					'secure' => false
				],
				'http://testhost/remote.php/webdav/testroot/',
			],
			[
				[
					'host' => 'testhost',
					'root' => 'testroot',
					'secure' => true
				],
				'https://testhost/remote.php/webdav/testroot/',
			],
			[
				[
					'host' => 'http://testhost',
					'root' => 'testroot',
					'secure' => false
				],
				'http://testhost/remote.php/webdav/testroot/',
			],
			[
				[
					'host' => 'https://testhost',
					'root' => 'testroot',
					'secure' => false
				],
				'https://testhost/remote.php/webdav/testroot/',
			],
			[
				[
					'host' => 'https://testhost/testroot',
					'root' => '',
					'secure' => false
				],
				'https://testhost/testroot/remote.php/webdav/',
			],
			[
				[
					'host' => 'https://testhost/testroot',
					'root' => 'subdir',
					'secure' => false
				],
				'https://testhost/testroot/remote.php/webdav/subdir/',
			],
			[
				[
					'host' => 'http://testhost/testroot',
					'root' => 'subdir',
					'secure' => true
				],
				'http://testhost/testroot/remote.php/webdav/subdir/',
			],
			[
				[
					'host' => 'http://testhost/testroot/',
					'root' => '/subdir',
					'secure' => false
				],
				'http://testhost/testroot/remote.php/webdav/subdir/',
			],
		];
	}

	/**
	 * @dataProvider configUrlProvider
	 */
	public function testConfig($config, $expectedUri): void {
		$config['user'] = 'someuser';
		$config['password'] = 'somepassword';
		$instance = new OwnCloud($config);
		$this->assertEquals($expectedUri, $instance->createBaseUri());
	}
}
