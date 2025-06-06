<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Service;

use OC\Files\Filesystem;
use OCA\Files_External\MountConfig;

use OCA\Files_External\Service\GlobalStoragesService;

/**
 * @group DB
 */
class GlobalStoragesServiceTest extends StoragesServiceTestCase {
	protected function setUp(): void {
		parent::setUp();
		$this->service = new GlobalStoragesService($this->backendService, $this->dbConfig, $this->mountCache, $this->eventDispatcher);
	}

	protected function tearDown(): void {
		@unlink($this->dataDir . '/mount.json');
		parent::tearDown();
	}

	protected function makeTestStorageData() {
		return $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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

	public static function storageDataProvider(): array {
		return [
			// all users
			[
				[
					'mountPoint' => 'mountpoint',
					'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
					'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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
					'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
					'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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
					'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
					'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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
					'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
					'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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
	public function testAddStorage($storageParams): void {
		$storage = $this->makeStorageConfig($storageParams);
		$newStorage = $this->service->addStorage($storage);

		$baseId = $newStorage->getId();

		$newStorage = $this->service->getStorage($baseId);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackend(), $newStorage->getBackend());
		$this->assertEquals($storage->getAuthMechanism(), $newStorage->getAuthMechanism());
		$this->assertEquals($storage->getBackendOptions(), $newStorage->getBackendOptions());
		$this->assertEquals($storage->getApplicableUsers(), $newStorage->getApplicableUsers());
		$this->assertEquals($storage->getApplicableGroups(), $newStorage->getApplicableGroups());
		$this->assertEquals($storage->getPriority(), $newStorage->getPriority());
		$this->assertEquals(0, $newStorage->getStatus());

		$nextStorage = $this->service->addStorage($storage);
		$this->assertEquals($baseId + 1, $nextStorage->getId());
	}

	/**
	 * @dataProvider storageDataProvider
	 */
	public function testUpdateStorage($updatedStorageParams): void {
		$updatedStorage = $this->makeStorageConfig($updatedStorageParams);
		$storage = $this->makeStorageConfig([
			'mountPoint' => 'mountpoint',
			'backendIdentifier' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanismIdentifier' => 'identifier:\Auth\Mechanism',
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
		$id = $newStorage->getId();

		$updatedStorage->setId($id);

		$this->service->updateStorage($updatedStorage);
		$newStorage = $this->service->getStorage($id);

		$this->assertEquals($updatedStorage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($updatedStorage->getBackendOptions()['password'], $newStorage->getBackendOptions()['password']);
		$this->assertEqualsCanonicalizing($updatedStorage->getApplicableUsers(), $newStorage->getApplicableUsers());
		$this->assertEquals($updatedStorage->getApplicableGroups(), $newStorage->getApplicableGroups());
		$this->assertEquals($updatedStorage->getPriority(), $newStorage->getPriority());
		$this->assertEquals(0, $newStorage->getStatus());
	}

	public static function hooksAddStorageDataProvider(): array {
		return [
			// applicable all
			[
				[],
				[],
				// expected hook calls
				[
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
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
						MountConfig::MOUNT_TYPE_USER,
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
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
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
						MountConfig::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
						'group2'
					],
				],
			],
		];
	}

	/**
	 * @dataProvider hooksAddStorageDataProvider
	 */
	public function testHooksAddStorage($applicableUsers, $applicableGroups, $expectedCalls): void {
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

	public static function hooksUpdateStorageDataProvider(): array {
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
						MountConfig::MOUNT_TYPE_USER,
						'all',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_GROUP,
						'group1'
					],
					// create the "all" entry
					[
						Filesystem::signal_create_mount,
						MountConfig::MOUNT_TYPE_USER,
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
		array $sourceApplicableUsers,
		array $sourceApplicableGroups,
		array $updatedApplicableUsers,
		array $updatedApplicableGroups,
		array $expectedCalls,
	): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers($sourceApplicableUsers);
		$storage->setApplicableGroups($sourceApplicableGroups);
		$storage = $this->service->addStorage($storage);

		$storage->setApplicableUsers($updatedApplicableUsers);
		$storage->setApplicableGroups($updatedApplicableGroups);

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


	public function testHooksRenameMountPoint(): void {
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
				MountConfig::MOUNT_TYPE_USER,
				'user1',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				MountConfig::MOUNT_TYPE_USER,
				'user2',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				MountConfig::MOUNT_TYPE_GROUP,
				'group1',
			],
			[
				Filesystem::signal_delete_mount,
				'/mountpoint',
				MountConfig::MOUNT_TYPE_GROUP,
				'group2',
			],
			// creates new one
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				MountConfig::MOUNT_TYPE_USER,
				'user1',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				MountConfig::MOUNT_TYPE_USER,
				'user2',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				MountConfig::MOUNT_TYPE_GROUP,
				'group1',
			],
			[
				Filesystem::signal_create_mount,
				'/renamedMountpoint',
				MountConfig::MOUNT_TYPE_GROUP,
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

	public static function hooksDeleteStorageDataProvider(): array {
		return [
			[
				['user1', 'user2'],
				['group1', 'group2'],
				// expected hook calls
				[
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_USER,
						'user1',
					],
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_USER,
						'user2',
					],
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_GROUP,
						'group1'
					],
					[
						Filesystem::signal_delete_mount,
						MountConfig::MOUNT_TYPE_GROUP,
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
						MountConfig::MOUNT_TYPE_USER,
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
		array $sourceApplicableUsers,
		array $sourceApplicableGroups,
		array $expectedCalls,
	): void {
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
}
