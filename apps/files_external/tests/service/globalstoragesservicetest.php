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

use \OCA\Files_external\Service\GlobalStoragesService;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

class GlobalStoragesServiceTest extends StoragesServiceTest {
	public function setUp() {
		parent::setUp();
		$this->service = new GlobalStoragesService();
	}

	public function tearDown() {
		@unlink($this->dataDir . '/mount.json');
		parent::tearDown();
	}

	protected function makeTestStorageData() {
		return $this->makeStorageConfig([ 
			'mountPoint' => 'mountpoint',
			'backendClass' => '\OC\Files\Storage\SMB',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'applicableUsers' => [],
			'applicableGroups' => [],
			'priority' => 15,
		]);
	}

	function storageDataProvider() {
		return [
			// all users
			[
				$this->makeStorageConfig([
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => [],
					'applicableGroups' => [],
					'priority' => 15,
				]),
			],
			// some users
			[
				$this->makeStorageConfig([
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => ['user1', 'user2'],
					'applicableGroups' => [],
					'priority' => 15,
				]),
			],
			// some groups
			[
				$this->makeStorageConfig([
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => [],
					'applicableGroups' => ['group1', 'group2'],
					'priority' => 15,
				]),
			],
			// both users and groups
			[
				$this->makeStorageConfig([
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => ['user1', 'user2'],
					'applicableGroups' => ['group1', 'group2'],
					'priority' => 15,
				]),
			],
		];
	}

	/**
	 * @dataProvider storageDataProvider
	 */
	public function testAddStorage($storage) {
		$newStorage = $this->service->addStorage($storage);

		$this->assertEquals(1, $newStorage->getId());


		$newStorage = $this->service->getStorage(1);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackendClass(), $newStorage->getBackendClass());
		$this->assertEquals($storage->getBackendOptions(), $newStorage->getBackendOptions());
		$this->assertEquals($storage->getApplicableUsers(), $newStorage->getApplicableUsers());
		$this->assertEquals($storage->getApplicableGroups(), $newStorage->getApplicableGroups());
		$this->assertEquals($storage->getPriority(), $newStorage->getPriority());
		$this->assertEquals(1, $newStorage->getId());
		$this->assertEquals(0, $newStorage->getStatus());

		// next one gets id 2
		$nextStorage = $this->service->addStorage($storage);
		$this->assertEquals(2, $nextStorage->getId());
	}

	/**
	 * @dataProvider storageDataProvider
	 */
	public function testUpdateStorage($updatedStorage) {
		$storage = $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendClass' => '\OC\Files\Storage\SMB',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'applicableUsers' => [],
			'applicableGroups' => [],
			'priority' => 15,
		]);

		$newStorage = $this->service->addStorage($storage);
		$this->assertEquals(1, $newStorage->getId());

		$updatedStorage->setId(1);

		$this->service->updateStorage($updatedStorage);
		$newStorage = $this->service->getStorage(1);

		$this->assertEquals($updatedStorage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($updatedStorage->getBackendOptions()['password'], $newStorage->getBackendOptions()['password']);
		$this->assertEquals($updatedStorage->getApplicableUsers(), $newStorage->getApplicableUsers());
		$this->assertEquals($updatedStorage->getApplicableGroups(), $newStorage->getApplicableGroups());
		$this->assertEquals($updatedStorage->getPriority(), $newStorage->getPriority());
		$this->assertEquals(1, $newStorage->getId());
		$this->assertEquals(0, $newStorage->getStatus());
	}

	function hooksAddStorageDataProvider() {
		return [
			// applicable all
			[
				[],
				[],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'all'
					],
				],
			],
			// single user
			[
				['user1'],
				[],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
				],
			],
			// single group
			[
				[],
				['group1'],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1',
					],
				],
			],
			// multiple users
			[
				['user1', 'user2'],
				[],
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
				],
			],
			// multiple groups
			[
				[],
				['group1', 'group2'],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
			// mixed groups and users 
			[
				['user1', 'user2'],
				['group1', 'group2'],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
		];
	}

	/**
	 * @dataProvider hooksAddStorageDataProvider
	 */
	public function testHooksAddStorage($applicableUsers, $applicableGroups, $expectedCalls) {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers($applicableUsers);
		$storage->setApplicableGroups($applicableGroups);
		$this->service->addStorage($storage);

		$this->assertCount(count($expectedCalls), self::$hookCalls);

		foreach ($expectedCalls as $index => $call) {
			$this->assertHookCall(
				self::$hookCalls[$index],
				$call[0],
				$storage->getMountPoint(),
				$call[1],
				$call[2]
			);
		}
	}

	function hooksUpdateStorageDataProvider() {
		return [
			[
				// nothing to multiple users and groups
				[],
				[],
				['user1', 'user2'],
				['group1', 'group2'],
				// expected hook calls
				[
					// delete the "all entry"
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'all',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
			[
				// adding a user and a group
				['user1'],
				['group1'],
				['user1', 'user2'],
				['group1', 'group2'],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
			[
				// removing a user and a group
				['user1', 'user2'],
				['group1', 'group2'],
				['user1'],
				['group1'],
				// expected hook calls
				[
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
			[
				// removing all
				['user1'],
				['group1'],
				[],
				[],
				// expected hook calls
				[
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1'
					],
					// create the "all" entry
					[
						Filesystem::signal_create_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'all'
					],
				],
			],
			[
				// no changes
				['user1'],
				['group1'],
				['user1'],
				['group1'],
				// no hook calls
				[]
			]
		];
	}

	/**
	 * @dataProvider hooksUpdateStorageDataProvider
	 */
	public function testHooksUpdateStorage(
		$sourceApplicableUsers,
		$sourceApplicableGroups,
		$updatedApplicableUsers,
		$updatedApplicableGroups,
	   	$expectedCalls) {

		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers($sourceApplicableUsers);
		$storage->setApplicableGroups($sourceApplicableGroups);
		$storage = $this->service->addStorage($storage);

		$storage->setapplicableUsers($updatedApplicableUsers);
		$storage->setapplicableGroups($updatedApplicableGroups);

		// reset calls
		self::$hookCalls = [];

		$this->service->updateStorage($storage);

		$this->assertCount(count($expectedCalls), self::$hookCalls);

		foreach ($expectedCalls as $index => $call) {
			$this->assertHookCall(
				self::$hookCalls[$index],
				$call[0],
				'/mountpoint',
				$call[1],
				$call[2]
			);
		}
	}

	/**
	 */
	public function testHooksRenameMountPoint() {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1', 'user2']);
		$storage->setApplicableGroups(['group1', 'group2']);
		$storage = $this->service->addStorage($storage);

		$storage->setMountPoint('renamedMountpoint');

		// reset calls
		self::$hookCalls = [];

		$this->service->updateStorage($storage);

		$expectedCalls = [
			// deletes old mount
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				\OC_Mount_Config::MOUNT_TYPE_USER,
				'user1',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				\OC_Mount_Config::MOUNT_TYPE_USER,
				'user2',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				\OC_Mount_Config::MOUNT_TYPE_GROUP,
				'group1',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				\OC_Mount_Config::MOUNT_TYPE_GROUP,
				'group2',
			],
			// creates new one
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				\OC_Mount_Config::MOUNT_TYPE_USER,
				'user1',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				\OC_Mount_Config::MOUNT_TYPE_USER,
				'user2',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				\OC_Mount_Config::MOUNT_TYPE_GROUP,
				'group1',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				\OC_Mount_Config::MOUNT_TYPE_GROUP,
				'group2',
			],
		];

		$this->assertCount(count($expectedCalls), self::$hookCalls);

		foreach ($expectedCalls as $index => $call) {
			$this->assertHookCall(
				self::$hookCalls[$index],
				$call[0],
				$call[1],
				$call[2],
				$call[3]
			);
		}
	}

	function hooksDeleteStorageDataProvider() {
		return [
			[
				['user1', 'user2'],
				['group1', 'group2'],
				// expected hook calls
				[
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
			[
				// deleting "all" entry
				[],
				[],
				[
					[
						Filesystem::signal_delete_mount,
						\OC_Mount_Config::MOUNT_TYPE_USER,
						'all',
					],
				],
			],
		];
	}

	/**
	 * @dataProvider hooksDeleteStorageDataProvider
	 */
	public function testHooksDeleteStorage(
		$sourceApplicableUsers,
		$sourceApplicableGroups,
	   	$expectedCalls) {

		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers($sourceApplicableUsers);
		$storage->setApplicableGroups($sourceApplicableGroups);
		$storage = $this->service->addStorage($storage);

		// reset calls
		self::$hookCalls = [];

		$this->service->removeStorage($storage->getId());

		$this->assertCount(count($expectedCalls), self::$hookCalls);

		foreach ($expectedCalls as $index => $call) {
			$this->assertHookCall(
				self::$hookCalls[$index],
				$call[0],
				'/mountpoint',
				$call[1],
				$call[2]
			);
		}
	}

	/**
	 * Make sure it uses the correct format when reading/writing
	 * the legacy config
	 */
	public function testLegacyConfigConversionApplicableAll() {
		$configFile = $this->dataDir . '/mount.json';

		$storage = $this->makeTestStorageData();
		$storage = $this->service->addStorage($storage);

		$json = json_decode(file_get_contents($configFile), true);

		$this->assertCount(1, $json);

		$this->assertEquals([\OC_Mount_Config::MOUNT_TYPE_USER], array_keys($json));
		$this->assertEquals(['all'], array_keys($json[\OC_Mount_config::MOUNT_TYPE_USER]));

		$mountPointData = $json[\OC_Mount_config::MOUNT_TYPE_USER]['all'];
		$this->assertEquals(['/$user/files/mountpoint'], array_keys($mountPointData));

		$mountPointOptions = current($mountPointData);
		$this->assertEquals(1, $mountPointOptions['id']);
		$this->assertEquals('\OC\Files\Storage\SMB', $mountPointOptions['class']);
		$this->assertEquals(15, $mountPointOptions['priority']);

		$backendOptions = $mountPointOptions['options'];
		$this->assertEquals('value1', $backendOptions['option1']);
		$this->assertEquals('value2', $backendOptions['option2']);
		$this->assertEquals('', $backendOptions['password']);
		$this->assertNotEmpty($backendOptions['password_encrypted']);
	}

	/**
	 * Make sure it uses the correct format when reading/writing
	 * the legacy config
	 */
	public function testLegacyConfigConversionApplicableUserAndGroup() {
		$configFile = $this->dataDir . '/mount.json';

		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1', 'user2']);
		$storage->setApplicableGroups(['group1', 'group2']);

		$storage = $this->service->addStorage($storage);

		$json = json_decode(file_get_contents($configFile), true);

		$this->assertCount(2, $json);

		$this->assertTrue(isset($json[\OC_Mount_Config::MOUNT_TYPE_USER]));
		$this->assertTrue(isset($json[\OC_Mount_Config::MOUNT_TYPE_GROUP]));
		$this->assertEquals(['user1', 'user2'], array_keys($json[\OC_Mount_config::MOUNT_TYPE_USER]));
		$this->assertEquals(['group1', 'group2'], array_keys($json[\OC_Mount_config::MOUNT_TYPE_GROUP]));

		// check that all options are the same for both users and both groups
		foreach ($json[\OC_Mount_Config::MOUNT_TYPE_USER] as $mountPointData) {
			$this->assertEquals(['/$user/files/mountpoint'], array_keys($mountPointData));

			$mountPointOptions = current($mountPointData);

			$this->assertEquals(1, $mountPointOptions['id']);
			$this->assertEquals('\OC\Files\Storage\SMB', $mountPointOptions['class']);
			$this->assertEquals(15, $mountPointOptions['priority']);

			$backendOptions = $mountPointOptions['options'];
			$this->assertEquals('value1', $backendOptions['option1']);
			$this->assertEquals('value2', $backendOptions['option2']);
			$this->assertEquals('', $backendOptions['password']);
			$this->assertNotEmpty($backendOptions['password_encrypted']);
		}

		foreach ($json[\OC_Mount_Config::MOUNT_TYPE_GROUP] as $mountPointData) {
			$this->assertEquals(['/$user/files/mountpoint'], array_keys($mountPointData));

			$mountPointOptions = current($mountPointData);

			$this->assertEquals(1, $mountPointOptions['id']);
			$this->assertEquals('\OC\Files\Storage\SMB', $mountPointOptions['class']);
			$this->assertEquals(15, $mountPointOptions['priority']);

			$backendOptions = $mountPointOptions['options'];
			$this->assertEquals('value1', $backendOptions['option1']);
			$this->assertEquals('value2', $backendOptions['option2']);
			$this->assertEquals('', $backendOptions['password']);
			$this->assertNotEmpty($backendOptions['password_encrypted']);
		}
	}

}
