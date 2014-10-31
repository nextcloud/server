<?php
/**
 * ownCloud
 *
 * @author Vincent Petry
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
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

		$this->service = new UserStoragesService($userSession);

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
			'backendClass' => '\OC\Files\Storage\SMB',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
		]);
	}

	public function testAddStorage() {
		$storage = $this->makeTestStorageData();

		$newStorage = $this->service->addStorage($storage);

		$this->assertEquals(1, $newStorage->getId());

		$newStorage = $this->service->getStorage(1);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackendClass(), $newStorage->getBackendClass());
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
			'backendClass' => '\OC\Files\Storage\SMB',
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
		$this->assertEquals('\OC\Files\Storage\SMB', $mountPointOptions['class']);

		$backendOptions = $mountPointOptions['options'];
		$this->assertEquals('value1', $backendOptions['option1']);
		$this->assertEquals('value2', $backendOptions['option2']);
		$this->assertEquals('', $backendOptions['password']);
		$this->assertNotEmpty($backendOptions['password_encrypted']);
	}
}
