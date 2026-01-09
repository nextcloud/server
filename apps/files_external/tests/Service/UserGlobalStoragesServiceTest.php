<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Service;

use OC\User\User;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\StoragesService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class UserGlobalStoragesServiceTest extends GlobalStoragesServiceTest {
	use UserTrait;

	protected IGroupManager&MockObject $groupManager;
	protected StoragesService $globalStoragesService;
	protected User $user;

	public const USER_ID = 'test_user';
	public const GROUP_ID = 'test_group';
	public const GROUP_ID2 = 'test_group2';

	protected function setUp(): void {
		parent::setUp();

		$this->globalStoragesService = $this->service;

		$this->user = new User(self::USER_ID, null, Server::get(IEventDispatcher::class));
		/** @var IUserSession&MockObject $userSession */
		$userSession = $this->createMock(IUserSession::class);
		$userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->groupManager->method('isInGroup')
			->willReturnCallback(function ($userId, $groupId) {
				if ($userId === self::USER_ID) {
					switch ($groupId) {
						case self::GROUP_ID:
						case self::GROUP_ID2:
							return true;
					}
				}
				return false;
			});
		$this->groupManager->method('getUserGroupIds')
			->willReturnCallback(function (IUser $user) {
				if ($user->getUID() === self::USER_ID) {
					return [self::GROUP_ID, self::GROUP_ID2];
				} else {
					return [];
				}
			});

		$this->service = new UserGlobalStoragesService(
			$this->backendService,
			$this->dbConfig,
			$userSession,
			$this->groupManager,
			$this->mountCache,
			$this->eventDispatcher,
			$this->appConfig,
		);
	}

	public static function applicableStorageProvider(): array {
		return [
			[[], [], true],

			// not applicable cases
			[['user1'], [], false],
			[[], ['group1'], false],
			[['user1'], ['group1'], false],

			// applicable cases
			[[self::USER_ID], [], true],
			[[], [self::GROUP_ID], true],
			[[self::USER_ID], ['group1'], true],
			[['user1'], [self::GROUP_ID], true],

			// sanity checks
			[['user1', 'user2', self::USER_ID, 'user3'], [], true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'applicableStorageProvider')]
	public function testGetStorageWithApplicable($applicableUsers, $applicableGroups, $isVisible): void {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig();
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);
		$storage->setApplicableUsers($applicableUsers);
		$storage->setApplicableGroups($applicableGroups);

		$newStorage = $this->globalStoragesService->addStorage($storage);

		$storages = $this->service->getAllStorages();
		if ($isVisible) {
			$this->assertEquals(1, count($storages));
			$retrievedStorage = $this->service->getStorage($newStorage->getId());
			$this->assertEquals('/mountpoint', $retrievedStorage->getMountPoint());
		} else {
			$this->assertEquals(0, count($storages));

			try {
				$this->service->getStorage($newStorage->getId());
				$this->fail('Failed asserting that storage can\'t be accessed by id');
			} catch (NotFoundException $e) {
			}
		}
	}


	public function testAddStorage($storageParams = null): void {
		$this->expectException(\DomainException::class);

		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$this->service->addStorage($storage);
	}


	public function testUpdateStorage($storageParams = null): void {
		$this->expectException(\DomainException::class);

		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);

		$newStorage = $this->globalStoragesService->addStorage($storage);

		$retrievedStorage = $this->service->getStorage($newStorage->getId());
		$retrievedStorage->setMountPoint('abc');
		$this->service->updateStorage($retrievedStorage);
	}


	public function testNonExistingStorage(): void {
		$this->expectException(\DomainException::class);

		$this->ActualNonExistingStorageTest();
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'deleteStorageDataProvider')]
	public function testDeleteStorage($backendOptions, $rustyStorageId): void {
		$this->expectException(\DomainException::class);

		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig(255);
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions($backendOptions);

		$newStorage = $this->globalStoragesService->addStorage($storage);
		$id = $newStorage->getId();

		$this->service->removeStorage($id);
	}


	public function testDeleteUnexistingStorage(): void {
		$this->expectException(\DomainException::class);

		$this->actualDeletedUnexistingStorageTest();
	}

	public static function getUniqueStoragesProvider(): array {
		return [
			// 'all' vs group
			[100, [], [], 100, [], [self::GROUP_ID], 2],
			[100, [], [self::GROUP_ID], 100, [], [], 1],

			// 'all' vs user
			[100, [], [], 100, [self::USER_ID], [], 2],
			[100, [self::USER_ID], [], 100, [], [], 1],

			// group vs user
			[100, [], [self::GROUP_ID], 100, [self::USER_ID], [], 2],
			[100, [self::USER_ID], [], 100, [], [self::GROUP_ID], 1],

			// group+user vs group
			[100, [], [self::GROUP_ID2], 100, [self::USER_ID], [self::GROUP_ID], 2],
			[100, [self::USER_ID], [self::GROUP_ID], 100, [], [self::GROUP_ID2], 1],

			// user vs 'all' (higher priority)
			[200, [], [], 100, [self::USER_ID], [], 2],
			[100, [self::USER_ID], [], 200, [], [], 1],

			// group vs group (higher priority)
			[100, [], [self::GROUP_ID2], 200, [], [self::GROUP_ID], 2],
			[200, [], [self::GROUP_ID], 100, [], [self::GROUP_ID2], 1],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'getUniqueStoragesProvider')]
	public function testGetUniqueStorages(
		$priority1, $applicableUsers1, $applicableGroups1,
		$priority2, $applicableUsers2, $applicableGroups2,
		$expectedPrecedence,
	): void {
		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$backend->method('isVisibleFor')
			->willReturn(true);
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');
		$authMechanism->method('isVisibleFor')
			->willReturn(true);

		$storage1 = new StorageConfig();
		$storage1->setMountPoint('mountpoint');
		$storage1->setBackend($backend);
		$storage1->setAuthMechanism($authMechanism);
		$storage1->setBackendOptions(['password' => 'testPassword']);
		$storage1->setPriority($priority1);
		$storage1->setApplicableUsers($applicableUsers1);
		$storage1->setApplicableGroups($applicableGroups1);

		$storage1 = $this->globalStoragesService->addStorage($storage1);

		$storage2 = new StorageConfig();
		$storage2->setMountPoint('mountpoint');
		$storage2->setBackend($backend);
		$storage2->setAuthMechanism($authMechanism);
		$storage2->setBackendOptions(['password' => 'testPassword']);
		$storage2->setPriority($priority2);
		$storage2->setApplicableUsers($applicableUsers2);
		$storage2->setApplicableGroups($applicableGroups2);

		$storage2 = $this->globalStoragesService->addStorage($storage2);

		$storages = $this->service->getUniqueStorages();
		$this->assertCount(1, $storages);

		if ($expectedPrecedence === 1) {
			$this->assertArrayHasKey($storage1->getID(), $storages);
		} elseif ($expectedPrecedence === 2) {
			$this->assertArrayHasKey($storage2->getID(), $storages);
		}
	}

	public function testGetStoragesBackendNotVisible(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testGetStoragesAuthMechanismNotVisible(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testHooksAddStorage($a = null, $b = null, $c = null): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testHooksUpdateStorage($a = null, $b = null, $c = null, $d = null, $e = null): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testHooksRenameMountPoint(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testHooksDeleteStorage($a = null, $b = null, $c = null): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testLegacyConfigConversionApplicableAll(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testLegacyConfigConversionApplicableUserAndGroup(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testReadLegacyConfigAndGenerateConfigId(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testReadLegacyConfigNoAuthMechanism(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testReadLegacyConfigClass(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testReadEmptyMountPoint(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}

	public function testUpdateStorageMountPoint(): void {
		// we don't test this here
		$this->addToAssertionCount(1);
	}
}
