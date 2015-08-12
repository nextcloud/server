<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace OCA\Files_external\Tests\Service;

use \OC\Files\Filesystem;

use \OCA\Files_external\Service\UserStoragesService;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

class UserStoragesServiceTest extends StoragesServiceTest {

	public function setUp() {
		parent::setUp();

		$this->userId = $this->getUniqueID('user_');

		$this->user = new \OC\User\User($this->userId, null);
		$userSession = $this->getMock('\OCP\IUserSession');
		$userSession
			->expects($this->any())
			->method('getUser')
			->will($this->returnValue($this->user));

		$this->service = new UserStoragesService($this->backendService, $userSession);

		// create home folder
		mkdir($this->dataDir . '/' . $this->userId . '/');
	}

	public function tearDown() {
		@unlink($this->dataDir . '/' . $this->userId . '/mount.json');
		parent::tearDown();
	}

	private function makeTestStorageData() {
		return $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'mountOptions' => [
				'preview' => false,
			]
		]);
	}

	public function testAddStorage() {
		$storage = $this->makeTestStorageData();

		$newStorage = $this->service->addStorage($storage);

		$this->assertEquals(1, $newStorage->getId());

		$newStorage = $this->service->getStorage(1);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackend(), $newStorage->getBackend());
		$this->assertEquals($storage->getAuthMechanism(), $newStorage->getAuthMechanism());
		$this->assertEquals($storage->getBackendOptions(), $newStorage->getBackendOptions());
		$this->assertEquals(1, $newStorage->getId());
		$this->assertEquals(0, $newStorage->getStatus());

		// hook called once for user
		$this->assertHookCall(
			current(self::$hookCalls),
			Filesystem::signal_create_mount,
			$storage->getMountPoint(),
			\OC_Mount_Config::MOUNT_TYPE_USER,
			$this->userId
		);

		// next one gets id 2
		$nextStorage = $this->service->addStorage($storage);
		$this->assertEquals(2, $nextStorage->getId());
	}

	public function testUpdateStorage() {
		$storage = $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
		]);

		$newStorage = $this->service->addStorage($storage);
		$this->assertEquals(1, $newStorage->getId());

		$backendOptions = $newStorage->getBackendOptions();
		$backendOptions['password'] = 'anotherPassword';
		$newStorage->setBackendOptions($backendOptions);

		self::$hookCalls = [];

		$newStorage = $this->service->updateStorage($newStorage);

		$this->assertEquals('anotherPassword', $newStorage->getBackendOptions()['password']);
		// these attributes are unused for user storages
		$this->assertEmpty($newStorage->getApplicableUsers());
		$this->assertEmpty($newStorage->getApplicableGroups());
		$this->assertEquals(1, $newStorage->getId());
		$this->assertEquals(0, $newStorage->getStatus());

		// no hook calls
		$this->assertEmpty(self::$hookCalls);
	}

	public function testDeleteStorage() {
		parent::testDeleteStorage();

		// hook called once for user (first one was during test creation)
		$this->assertHookCall(
			self::$hookCalls[1],
			Filesystem::signal_delete_mount,
			'/mountpoint',
			\OC_Mount_Config::MOUNT_TYPE_USER,
			$this->userId
		);
	}

	public function testHooksRenameMountPoint() {
		$storage = $this->makeTestStorageData();
		$storage = $this->service->addStorage($storage);

		$storage->setMountPoint('renamedMountpoint');

		// reset calls
		self::$hookCalls = [];

		$this->service->updateStorage($storage);

		// hook called twice
		$this->assertHookCall(
			self::$hookCalls[0],
			Filesystem::signal_delete_mount,
			'/mountpoint',
			\OC_Mount_Config::MOUNT_TYPE_USER,
			$this->userId
		);
		$this->assertHookCall(
			self::$hookCalls[1],
			Filesystem::signal_create_mount,
			'/renamedMountpoint',
			\OC_Mount_Config::MOUNT_TYPE_USER,
			$this->userId
		);
	}

	/**
	 * Make sure it uses the correct format when reading/writing
	 * the legacy config
	 */
	public function testLegacyConfigConversion() {
		$configFile = $this->dataDir . '/' . $this->userId . '/mount.json';

		$storage = $this->makeTestStorageData();
		$storage = $this->service->addStorage($storage);

		$json = json_decode(file_get_contents($configFile), true);

		$this->assertCount(1, $json);

		$this->assertEquals([\OC_Mount_Config::MOUNT_TYPE_USER], array_keys($json));
		$this->assertEquals([$this->userId], array_keys($json[\OC_Mount_config::MOUNT_TYPE_USER]));

		$mountPointData = $json[\OC_Mount_config::MOUNT_TYPE_USER][$this->userId];
		$this->assertEquals(['/' . $this->userId . '/files/mountpoint'], array_keys($mountPointData));

		$mountPointOptions = current($mountPointData);
		$this->assertEquals(1, $mountPointOptions['id']);
		$this->assertEquals('identifier:\OCA\Files_External\Lib\Backend\SMB', $mountPointOptions['backend']);
		$this->assertEquals('identifier:\Auth\Mechanism', $mountPointOptions['authMechanism']);
		$this->assertEquals(false, $mountPointOptions['mountOptions']['preview']);

		$backendOptions = $mountPointOptions['options'];
		$this->assertEquals('value1', $backendOptions['option1']);
		$this->assertEquals('value2', $backendOptions['option2']);
		$this->assertEquals('', $backendOptions['password']);
		$this->assertNotEmpty($backendOptions['password_encrypted']);
	}

	/**
	 * Test reading in a legacy config and generating config ids.
	 */
	public function testReadLegacyConfigAndGenerateConfigId() {
		$configFile = $this->dataDir . '/' . $this->userId . '/mount.json';

		$legacyBackendOptions = [
			'user' => 'someuser',
			'password' => 'somepassword',
		];
		$legacyBackendOptions = \OC_Mount_Config::encryptPasswords($legacyBackendOptions);

		$legacyConfig = [
			'backend' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanism' => 'identifier:\Auth\Mechanism',
			'options' => $legacyBackendOptions,
			'mountOptions' => ['preview' => false],
		];
		// different mount options
		$legacyConfig2 = [
			'backend' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanism' => 'identifier:\Auth\Mechanism',
			'options' => $legacyBackendOptions,
			'mountOptions' => ['preview' => true],
		];

		$json = ['user' => []];
		$json['user'][$this->userId] = [
			'/$user/files/somemount' => $legacyConfig,
			'/$user/files/anothermount' => $legacyConfig2,
		];

		file_put_contents($configFile, json_encode($json));

		$allStorages = $this->service->getAllStorages();

		$this->assertCount(2, $allStorages);

		$storage1 = $allStorages[1];
		$storage2 = $allStorages[2];

		$this->assertEquals('/somemount', $storage1->getMountPoint());
		$this->assertEquals('someuser', $storage1->getBackendOptions()['user']);
		$this->assertEquals('somepassword', $storage1->getBackendOptions()['password']);
		$this->assertEquals(['preview' => false], $storage1->getMountOptions());

		$this->assertEquals('/anothermount', $storage2->getMountPoint());
		$this->assertEquals('someuser', $storage2->getBackendOptions()['user']);
		$this->assertEquals('somepassword', $storage2->getBackendOptions()['password']);
		$this->assertEquals(['preview' => true], $storage2->getMountOptions());
	}
}
