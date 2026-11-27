<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Service;

use OCA\Files_External\Event\StorageCreatedEvent;
use OCA\Files_External\Event\StorageDeletedEvent;
use OCA\Files_External\Event\StorageUpdatedEvent;
use OCA\Files_External\Service\GlobalStoragesService;
use OCP\Files\Events\InvalidateMountCacheEvent;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class GlobalStoragesServiceTest extends StoragesServiceTestCase {
	protected function setUp(): void {
		parent::setUp();
		$this->service = new GlobalStoragesService($this->backendService, $this->dbConfig, $this->eventDispatcher, $this->appConfig);
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'storageDataProvider')]
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

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'storageDataProvider')]
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

	public function testEventsAddStorage(): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1']);
		$storage->setApplicableGroups(['group1']);
		$storage = $this->service->addStorage($storage);

		$this->assertDispatchedEvents([StorageCreatedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertSame($storage, $this->dispatchedEvents[0]->getNewConfig());
	}

	public function testEventsUpdateStorage(): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1']);
		$storage = $this->service->addStorage($storage);

		$storage->setApplicableUsers(['user1', 'user2']);
		$this->dispatchedEvents = [];

		$this->service->updateStorage($storage);

		$this->assertDispatchedEvents([StorageUpdatedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertEquals(['user1'], $this->dispatchedEvents[0]->getOldConfig()->getApplicableUsers());
		$this->assertEqualsCanonicalizing(['user1', 'user2'], $this->dispatchedEvents[0]->getNewConfig()->getApplicableUsers());
	}

	public function testEventsRenameMountPoint(): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1', 'user2']);
		$storage->setApplicableGroups(['group1', 'group2']);
		$storage = $this->service->addStorage($storage);

		$storage->setMountPoint('renamedMountpoint');
		$this->dispatchedEvents = [];

		$this->service->updateStorage($storage);

		$this->assertDispatchedEvents([StorageDeletedEvent::class, StorageCreatedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertEquals('/mountpoint', $this->dispatchedEvents[0]->getOldConfig()->getMountPoint());
		$this->assertEquals('/renamedMountpoint', $this->dispatchedEvents[1]->getNewConfig()->getMountPoint());
	}

	public function testEventsDeleteStorage(): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1']);
		$storage->setApplicableGroups(['group1']);
		$storage = $this->service->addStorage($storage);
		$this->dispatchedEvents = [];

		$this->service->removeStorage($storage->getId());

		$this->assertDispatchedEvents([StorageDeletedEvent::class, InvalidateMountCacheEvent::class]);
		$this->assertEquals($storage->getId(), $this->dispatchedEvents[0]->getOldConfig()->getId());
	}

	public function testAuditEvents(): void {
		$storage = $this->makeTestStorageData();
		$storage->setApplicableUsers(['user1', 'user2']);
		$storage->setApplicableGroups(['group1']);
		$storage = $this->service->addStorage($storage);
		$id = $storage->getId();

		$storage->setApplicableUsers(['user3']);
		$storage->setApplicableGroups([]);
		$this->service->updateStorage($storage);

		$this->service->removeStorage($id);

		$this->assertCount(3, $this->auditEvents);

		$this->assertSame('files_external.storage.created', $this->auditEvents[0]->getOperation());
		$this->assertSame('External storage "%s" was created with mount point "%s", backend "%s", authentication mechanism "%s", applicable users "%s", applicable groups "%s" and scope "%s"', $this->auditEvents[0]->getLogMessage());
		$this->assertSame([
			'storageId' => $id,
			'mountPoint' => '/mountpoint',
			'backend' => 'identifier:\OCA\Files_External\Lib\Backend\SMB',
			'authMechanism' => 'identifier:\Auth\Mechanism',
			'applicableUsers' => 'user1, user2',
			'applicableGroups' => 'group1',
			'scope' => 'admin',
		], $this->auditEvents[0]->getParameters());

		$this->assertSame('files_external.storage.updated', $this->auditEvents[1]->getOperation());
		$this->assertSame('External storage "%s" was updated with mount point "%s", applicable users "%s" and applicable groups "%s"', $this->auditEvents[1]->getLogMessage());
		$this->assertSame([
			'storageId' => $id,
			'mountPoint' => '/mountpoint',
			'applicableUsers' => 'user3',
			'applicableGroups' => '',
		], $this->auditEvents[1]->getParameters());

		$this->assertSame('files_external.storage.deleted', $this->auditEvents[2]->getOperation());
		$this->assertSame('External storage "%s" with mount point "%s" and scope "%s" was deleted', $this->auditEvents[2]->getLogMessage());
		$this->assertSame([
			'storageId' => $id,
			'mountPoint' => '/mountpoint',
			'scope' => 'admin',
		], $this->auditEvents[2]->getParameters());

		foreach ($this->auditEvents as $event) {
			$this->assertStringNotContainsString('testPassword', json_encode($event->getParameters()));
		}
	}
}
