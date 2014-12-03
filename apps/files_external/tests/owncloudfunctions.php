<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Storage;

class OwnCloudFunctions extends \Test\TestCase {

	function configUrlProvider() {
		return array(
			array(
				array(
					'host' => 'testhost',
					'root' => 'testroot',
					'secure' => false
				),
				'http://testhost/remote.php/webdav/testroot/',
			),
			array(
				array(
					'host' => 'testhost',
					'root' => 'testroot',
					'secure' => true
				),
				'https://testhost/remote.php/webdav/testroot/',
			),
			array(
				array(
					'host' => 'http://testhost',
					'root' => 'testroot',
					'secure' => false
				),
				'http://testhost/remote.php/webdav/testroot/',
			),
			array(
				array(
					'host' => 'https://testhost',
					'root' => 'testroot',
					'secure' => false
				),
				'https://testhost/remote.php/webdav/testroot/',
			),
			array(
				array(
					'host' => 'https://testhost/testroot',
					'root' => '',
					'secure' => false
				),
				'https://testhost/testroot/remote.php/webdav/',
			),
			array(
				array(
					'host' => 'https://testhost/testroot',
					'root' => 'subdir',
					'secure' => false
				),
				'https://testhost/testroot/remote.php/webdav/subdir/',
			),
			array(
				array(
					'host' => 'http://testhost/testroot',
					'root' => 'subdir',
					'secure' => true
				),
				'http://testhost/testroot/remote.php/webdav/subdir/',
			),
		);
	}

	/**
	 * @dataProvider configUrlProvider
	 */
	public function testConfig($config, $expectedUri) {
		$config['user'] = 'someuser';
		$config['password'] = 'somepassword';
		$instance = new \OC\Files\Storage\OwnCloud($config);
		$this->assertEquals($expectedUri, $instance->createBaseUri());
	}
}
