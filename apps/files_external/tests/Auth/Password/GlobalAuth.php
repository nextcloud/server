<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Tests\Auth\Password;

use OCA\Files_External\Lib\Auth\Password\GlobalAuth;
use OCA\Files_external\Lib\StorageConfig;
use Test\TestCase;

class GlobalAuthTest extends TestCase {
	/**
	 * @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $l10n;

	/**
	 * @var \OCP\Security\ICredentialsManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	private $credentialsManager;

	/**
	 * @var GlobalAuth
	 */
	private $instance;

	protected function setUp() {
		parent::setUp();
		$this->l10n = $this->getMock('\OCP\IL10N');
		$this->credentialsManager = $this->getMock('\OCP\Security\ICredentialsManager');
		$this->instance = new GlobalAuth($this->l10n, $this->credentialsManager);
	}

	private function getStorageConfig($type, $config = []) {
		/** @var \OCA\Files_External\Lib\StorageConfig|\PHPUnit_Framework_MockObject_MockObject $storageConfig */
		$storageConfig = $this->getMock('\OCA\Files_External\Lib\StorageConfig');
		$storageConfig->expects($this->any())
			->method('getType')
			->will($this->returnValue($type));
		$storageConfig->expects($this->any())
			->method('getBackendOptions')
			->will($this->returnCallback(function () use (&$config) {
				return $config;
			}));
		$storageConfig->expects($this->any())
			->method('getBackendOption')
			->will($this->returnCallback(function ($key) use (&$config) {
				return $config[$key];
			}));
		$storageConfig->expects($this->any())
			->method('setBackendOption')
			->will($this->returnCallback(function ($key, $value) use (&$config) {
				$config[$key] = $value;
			}));

		return $storageConfig;
	}

	public function testNoCredentials() {
		$this->credentialsManager->expects($this->once())
			->method('retrieve')
			->will($this->returnValue(null));

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_ADMIN);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([], $storage->getBackendOptions());
	}

	public function testSavedCredentials() {
		$this->credentialsManager->expects($this->once())
			->method('retrieve')
			->will($this->returnValue([
				'user' => 'a',
				'password' => 'b'
			]));

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_ADMIN);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([
			'user' => 'a',
			'password' => 'b'
		], $storage->getBackendOptions());
	}

	/**
	 * @expectedException \OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException
	 */
	public function testNoCredentialsPersonal() {
		$this->credentialsManager->expects($this->never())
			->method('retrieve');

		$storage = $this->getStorageConfig(StorageConfig::MOUNT_TYPE_PERSONAl);

		$this->instance->manipulateStorageConfig($storage);
		$this->assertEquals([], $storage->getBackendOptions());
	}

}
