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

use \OCA\Files_external\Service\GlobalStoragesService;
use \OCA\Files_external\NotFoundException;
use \OCA\Files_external\Lib\StorageConfig;

class GlobalStoragesServiceTest extends StoragesServiceTest {
	public function setUp() {
		parent::setUp();
		$this->service = new GlobalStoragesService($this->backendService);
	}

	public function tearDown() {
		@unlink($this->dataDir . '/mount.json');
		parent::tearDown();
	}

	protected function makeTestStorageData() {
		return $this->makeStorageConfig([ 
			'mountPoint' => 'mountpoint',
			'backendClass' => '\OC\Files\Storage\SMB',
			'authMechanismClass' => '\Auth\Mechanism',
			'backendOptions' => [
				'option1' => 'value1',
				'option2' => 'value2',
				'password' => 'testPassword',
			],
			'applicableUsers' => [],
			'applicableGroups' => [],
			'priority' => 15,
			'mountOptions' => [
				'preview' => false,
			]
		]);
	}

	function storageDataProvider() {
		return [
			// all users
			[
				[
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'authMechanismClass' => '\Auth\Mechanism',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => [],
					'applicableGroups' => [],
					'priority' => 15,
				],
			],
			// some users
			[
				[
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'authMechanismClass' => '\Auth\Mechanism',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => ['user1', 'user2'],
					'applicableGroups' => [],
					'priority' => 15,
				],
			],
			// some groups
			[
				[
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'authMechanismClass' => '\Auth\Mechanism',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => [],
					'applicableGroups' => ['group1', 'group2'],
					'priority' => 15,
				],
			],
			// both users and groups
			[
				[
					'mountPoint' => 'mountpoint',
					'backendClass' => '\OC\Files\Storage\SMB',
					'authMechanismClass' => '\Auth\Mechanism',
					'backendOptions' => [
						'option1' => 'value1',
						'option2' => 'value2',
						'password' => 'testPassword',
					],
					'applicableUsers' => ['user1', 'user2'],
					'applicableGroups' => ['group1', 'group2'],
					'priority' => 15,
				],
			],
		];
	}

	/**
	 * @dataProvider storageDataProvider
	 */
	public function testAddStorage($storageParams) {
		$storage = $this->makeStorageConfig($storageParams);
		$newStorage = $this->service->addStorage($storage);

		$this->assertEquals(1, $newStorage->getId());


		$newStorage = $this->service->getStorage(1);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackend(), $newStorage->getBackend());
		$this->assertEquals($storage->getAuthMechanism(), $newStorage->getAuthMechanism());
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
	public function testUpdateStorage($updatedStorageParams) {
		$updatedStorage = $this->makeStorageConfig($updatedStorageParams);
		$storage = $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendClass' => '\OC\Files\Storage\SMB',
			'authMechanismClass' => '\Auth\Mechanism',
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
		$this->assertEquals('\Auth\Mechanism', $mountPointOptions['authMechanism']);
		$this->assertEquals(15, $mountPointOptions['priority']);
		$this->assertEquals(false, $mountPointOptions['mountOptions']['preview']);

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
			$this->assertEquals('\Auth\Mechanism', $mountPointOptions['authMechanism']);
			$this->assertEquals(15, $mountPointOptions['priority']);
			$this->assertEquals(false, $mountPointOptions['mountOptions']['preview']);

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
			$this->assertEquals('\Auth\Mechanism', $mountPointOptions['authMechanism']);
			$this->assertEquals(15, $mountPointOptions['priority']);
			$this->assertEquals(false, $mountPointOptions['mountOptions']['preview']);

			$backendOptions = $mountPointOptions['options'];
			$this->assertEquals('value1', $backendOptions['option1']);
			$this->assertEquals('value2', $backendOptions['option2']);
			$this->assertEquals('', $backendOptions['password']);
			$this->assertNotEmpty($backendOptions['password_encrypted']);
		}
	}

	/**
	 * Test reading in a legacy config and generating config ids.
	 */
	public function testReadLegacyConfigAndGenerateConfigId() {
		$configFile = $this->dataDir . '/mount.json';

		$legacyBackendOptions = [
			'user' => 'someuser',
			'password' => 'somepassword',
		];
		$legacyBackendOptions = \OC_Mount_Config::encryptPasswords($legacyBackendOptions);

		$legacyConfig = [
			'class' => '\OC\Files\Storage\SMB',
			'authMechanism' => '\Auth\Mechanism',
			'options' => $legacyBackendOptions,
			'mountOptions' => ['preview' => false],
		];
		// different mount options
		$legacyConfig2 = [
			'class' => '\OC\Files\Storage\SMB',
			'authMechanism' => '\Auth\Mechanism',
			'options' => $legacyBackendOptions,
			'mountOptions' => ['preview' => true],
		];

		$legacyBackendOptions2 = $legacyBackendOptions;
		$legacyBackendOptions2 = ['user' => 'someuser2', 'password' => 'somepassword2'];
		$legacyBackendOptions2 = \OC_Mount_Config::encryptPasswords($legacyBackendOptions2);

		// different config
		$legacyConfig3 = [
			'class' => '\OC\Files\Storage\SMB',
			'authMechanism' => '\Auth\Mechanism',
			'options' => $legacyBackendOptions2,
			'mountOptions' => ['preview' => true],
		];

		$json = [
			'user' => [
				'user1' => [
					'/$user/files/somemount' => $legacyConfig,
				],
				// same config
				'user2' => [
					'/$user/files/somemount' => $legacyConfig,
				],
				// different mountOptions
				'user3' => [
					'/$user/files/somemount' => $legacyConfig2,
				],
				// different mount point
				'user4' => [
					'/$user/files/anothermount' => $legacyConfig,
				],
				// different storage config
				'user5' => [
					'/$user/files/somemount' => $legacyConfig3,
				],
			],
			'group' => [
				'group1' => [
					// will get grouped with user configs
					'/$user/files/somemount' => $legacyConfig,
				],
			],
		];

		file_put_contents($configFile, json_encode($json));

		$allStorages = $this->service->getAllStorages();

		$this->assertCount(4, $allStorages);

		$storage1 = $allStorages[1];
		$storage2 = $allStorages[2];
		$storage3 = $allStorages[3];
		$storage4 = $allStorages[4];

		$this->assertEquals('/somemount', $storage1->getMountPoint());
		$this->assertEquals('someuser', $storage1->getBackendOptions()['user']);
		$this->assertEquals('somepassword', $storage1->getBackendOptions()['password']);
		$this->assertEquals(['user1', 'user2'], $storage1->getApplicableUsers());
		$this->assertEquals(['group1'], $storage1->getApplicableGroups());
		$this->assertEquals(['preview' => false], $storage1->getMountOptions());

		$this->assertEquals('/somemount', $storage2->getMountPoint());
		$this->assertEquals('someuser', $storage2->getBackendOptions()['user']);
		$this->assertEquals('somepassword', $storage2->getBackendOptions()['password']);
		$this->assertEquals(['user3'], $storage2->getApplicableUsers());
		$this->assertEquals([], $storage2->getApplicableGroups());
		$this->assertEquals(['preview' => true], $storage2->getMountOptions());

		$this->assertEquals('/anothermount', $storage3->getMountPoint());
		$this->assertEquals('someuser', $storage3->getBackendOptions()['user']);
		$this->assertEquals('somepassword', $storage3->getBackendOptions()['password']);
		$this->assertEquals(['user4'], $storage3->getApplicableUsers());
		$this->assertEquals([], $storage3->getApplicableGroups());
		$this->assertEquals(['preview' => false], $storage3->getMountOptions());

		$this->assertEquals('/somemount', $storage4->getMountPoint());
		$this->assertEquals('someuser2', $storage4->getBackendOptions()['user']);
		$this->assertEquals('somepassword2', $storage4->getBackendOptions()['password']);
		$this->assertEquals(['user5'], $storage4->getApplicableUsers());
		$this->assertEquals([], $storage4->getApplicableGroups());
		$this->assertEquals(['preview' => true], $storage4->getMountOptions());
	}
}
