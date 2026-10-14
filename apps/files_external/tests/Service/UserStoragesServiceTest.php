<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Service;

use OC\User\User;
use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\StoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\Files\Events\InvalidateMountCacheEvent;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\Traits\UserTrait;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class UserStoragesServiceTest extends StoragesServiceTestCase {
	use UserTrait;

	protected User $user;

	protected string $userId;
	protected StoragesService $globalStoragesService;

	protected function setUp(): void {
		parent::setUp();

		$this->globalStoragesService = new GlobalStoragesService($this->backendService, $this->dbConfig, $this->eventDispatcher, $this->appConfig);

		$this->userId = $this->getUniqueID('user_');
		$this->createUser($this->userId, $this->userId);
		$this->user = Server::get(IUserManager::class)->get($this->userId);

		/** @var IUserSession&MockObject $userSession */
		$userSession = $this->createMock(IUserSession::class);
		$userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		$this->service = new UserStoragesService($this->backendService, $this->dbConfig, $userSession, $this->eventDispatcher, $this->appConfig);
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

	public function testAddStorage(): void {
		$storage = $this->makeTestStorageData();

		$newStorage = $this->service->addStorage($storage);

		$id = $newStorage->getId();

		$newStorage = $this->service->getStorage($id);

		$this->assertEquals($storage->getMountPoint(), $newStorage->getMountPoint());
		$this->assertEquals($storage->getBackend(), $newStorage->getBackend());
		$this->assertEquals($storage->getAuthMechanism(), $newStorage->getAuthMechanism());
		$this->assertEquals($storage->getBackendOptions(), $newStorage->getBackendOptions());
		$this->assertSame($storage->getMountOptions(), $newStorage->getMountOptions());
		$this->assertEquals(0, $newStorage->getStatus());

		$this->assertDispatchedEvents([StorageCreatedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertEquals([$this->userId], $this->dispatchedEvents[0]->getNewConfig()->getApplicableUsers());

		$nextStorage = $this->service->addStorage($storage);
		$this->assertEquals($id + 1, $nextStorage->getId());
	}

	public function testUpdateStorage(): void {
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

		$backendOptions = $newStorage->getBackendOptions();
		$backendOptions['password'] = 'anotherPassword';
		$newStorage->setBackendOptions($backendOptions);

		$this->dispatchedEvents = [];

		$newStorage = $this->service->updateStorage($newStorage);

		$this->assertEquals('anotherPassword', $newStorage->getBackendOptions()['password']);
		$this->assertEquals([$this->userId], $newStorage->getApplicableUsers());
		// these attributes are unused for user storages
		$this->assertEmpty($newStorage->getApplicableGroups());
		$this->assertEquals(0, $newStorage->getStatus());

		$this->assertEmpty($this->dispatchedEvents);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'deleteStorageDataProvider')]
	public function testDeleteStorage($backendOptions, $rustyStorageId): void {
		parent::testDeleteStorage($backendOptions, $rustyStorageId);

		$this->assertDispatchedEvents([
			StorageCreatedEvent::class,
			InvalidateMountCacheEvent::class,
			StorageDeletedEvent::class,
			InvalidateMountCacheEvent::class,
		]);
		$this->assertEquals('/mountpoint', $this->dispatchedEvents[2]->getOldConfig()->getMountPoint());
	}

	public function testEventsRenameMountPoint(): void {
		$storage = $this->makeTestStorageData();
		$storage = $this->service->addStorage($storage);

		$storage->setMountPoint('renamedMountpoint');

		$this->dispatchedEvents = [];

		$this->service->updateStorage($storage);

		$this->assertDispatchedEvents([StorageDeletedEvent::class, StorageCreatedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertEquals('/mountpoint', $this->dispatchedEvents[0]->getOldConfig()->getMountPoint());
		$this->assertEquals('/renamedMountpoint', $this->dispatchedEvents[1]->getNewConfig()->getMountPoint());
	}

	public function testGetAdminStorage(): void {
		$this->expectException(NotFoundException::class);

		$backend = $this->backendService->getBackend('identifier:\OCA\Files_External\Lib\Backend\SMB');
		$authMechanism = $this->backendService->getAuthMechanism('identifier:\Auth\Mechanism');

		$storage = new StorageConfig();
		$storage->setMountPoint('mountpoint');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		$storage->setBackendOptions(['password' => 'testPassword']);
		$storage->setApplicableUsers([$this->userId]);

		$newStorage = $this->globalStoragesService->addStorage($storage);

		$this->assertInstanceOf('\OCA\Files_External\Lib\StorageConfig', $this->globalStoragesService->getStorage($newStorage->getId()));

		$this->service->getStorage($newStorage->getId());
	}

	public function testAuditEventsPersonalScope(): void {
		$auditEvents = [];
		$this->eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (object $event) use (&$auditEvents): void {
				if ($event instanceof CriticalActionPerformedEvent) {
					$auditEvents[] = $event;
				}
			});

		$storage = $this->service->addStorage($this->makeTestStorageData());
		$this->service->removeStorage($storage->getId());

		$this->assertCount(2, $auditEvents);
		$this->assertSame('files_external.storage.created', $auditEvents[0]->getOperation());
		$this->assertSame($this->userId, $auditEvents[0]->getParameters()['applicableUsers']);
		$this->assertSame('personal', $auditEvents[0]->getParameters()['scope']);
		$this->assertSame('files_external.storage.deleted', $auditEvents[1]->getOperation());
		$this->assertSame('personal', $auditEvents[1]->getParameters()['scope']);
	}
}
