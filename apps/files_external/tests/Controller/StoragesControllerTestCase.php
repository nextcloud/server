<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Controller;

use OCA\Files_External\Controller\GlobalStoragesController;
use OCA\Files_External\Controller\UserStoragesController;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\NullMechanism;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\Backend\SMB;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\MountConfig;
use OCA\Files_External\NotFoundException;
use OCA\Files_External\Service\GlobalStoragesService;
use OCA\Files_External\Service\UserStoragesService;
use OCP\AppFramework\Http;
use PHPUnit\Framework\MockObject\MockObject;

abstract class StoragesControllerTestCase extends \Test\TestCase {
	protected GlobalStoragesController|UserStoragesController $controller;
	protected GlobalStoragesService|UserStoragesService|MockObject $service;

	protected function setUp(): void {
		parent::setUp();
		MountConfig::$skipTest = true;
	}

	protected function tearDown(): void {
		MountConfig::$skipTest = false;
		parent::tearDown();
	}

	/**
	 * @return \OCA\Files_External\Lib\Backend\Backend&MockObject
	 */
	protected function getBackendMock($class = SMB::class, $storageClass = \OCA\Files_External\Lib\Storage\SMB::class) {
		$backend = $this->createMock(Backend::class);
		$backend->method('getStorageClass')
			->willReturn($storageClass);
		$backend->method('getIdentifier')
			->willReturn('identifier:' . $class);
		$backend->method('getParameters')
			->willReturn([]);
		return $backend;
	}

	/**
	 * @return AuthMechanism|MockObject
	 */
	protected function getAuthMechMock($scheme = 'null', $class = NullMechanism::class) {
		$authMech = $this->createMock(AuthMechanism::class);
		$authMech->method('getScheme')
			->willReturn($scheme);
		$authMech->method('getIdentifier')
			->willReturn('identifier:' . $class);
		$authMech->method('getParameters')
			->willReturn([]);

		return $authMech;
	}

	public function testAddStorage(): void {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->willReturn($storageConfig);
		$this->service->expects($this->once())
			->method('addStorage')
			->willReturn($storageConfig);

		$response = $this->controller->create(
			'mount',
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$data = $response->getData();
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertEquals($storageConfig->jsonSerialize(), $data);
	}

	public function testAddLocalStorageWhenDisabled(): void {
		$authMech = $this->getAuthMechMock();
		$backend = $this->getBackendMock();

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->never())
			->method('createStorage');
		$this->service->expects($this->never())
			->method('addStorage');

		$response = $this->controller->create(
			'mount',
			'local',
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$data = $response->getData();
		$this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function testUpdateStorage(): void {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->willReturn($storageConfig);
		$this->service->expects($this->once())
			->method('updateStorage')
			->willReturn($storageConfig);

		$response = $this->controller->update(
			1,
			'mount',
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$data = $response->getData();
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals($storageConfig->jsonSerialize(), $data);
	}

	public static function mountPointNamesProvider(): array {
		return [
			[''],
			['/'],
			['//'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'mountPointNamesProvider')]
	public function testAddOrUpdateStorageInvalidMountPoint($mountPoint): void {
		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint($mountPoint);
		$storageConfig->setBackend($this->getBackendMock());
		$storageConfig->setAuthMechanism($this->getAuthMechMock());
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->willReturn($storageConfig);
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			$mountPoint,
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			$mountPoint,
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testAddOrUpdateStorageInvalidBackend(): void {
		$this->service->expects($this->exactly(2))
			->method('createStorage')
			->willThrowException(new \InvalidArgumentException());
		$this->service->expects($this->never())
			->method('addStorage');
		$this->service->expects($this->never())
			->method('updateStorage');

		$response = $this->controller->create(
			'mount',
			'\OC\Files\Storage\InvalidStorage',
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());

		$response = $this->controller->update(
			1,
			'mount',
			'\OC\Files\Storage\InvalidStorage',
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
	}

	public function testUpdateStorageNonExisting(): void {
		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn(true);
		$authMech->method('isVisibleFor')
			->willReturn(true);
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn(true);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig(255);
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->willReturn($storageConfig);
		$this->service->expects($this->once())
			->method('updateStorage')
			->willThrowException(new NotFoundException());

		$response = $this->controller->update(
			255,
			'mount',
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testDeleteStorage(): void {
		$this->service->expects($this->once())
			->method('removeStorage');

		$response = $this->controller->destroy(1);
		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
	}

	public function testDeleteStorageNonExisting(): void {
		$this->service->expects($this->once())
			->method('removeStorage')
			->willThrowException(new NotFoundException());

		$response = $this->controller->destroy(255);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testGetStorage(): void {
		$backend = $this->getBackendMock();
		$authMech = $this->getAuthMechMock();
		$storageConfig = new StorageConfig(1);
		$storageConfig->setMountPoint('test');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions(['user' => 'test', 'password', 'password123']);
		$storageConfig->setMountOptions(['priority' => false]);

		$this->service->expects($this->once())
			->method('getStorage')
			->with(1)
			->willReturn($storageConfig);
		$response = $this->controller->show(1);

		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$expected = $storageConfig->jsonSerialize();
		$expected['can_edit'] = false;
		$this->assertEquals($expected, $response->getData());
	}

	public static function validateStorageProvider(): array {
		return [
			[true, true, true],
			[false, true, false],
			[true, false, false],
			[false, false, false]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'validateStorageProvider')]
	public function testValidateStorage(bool $backendValidate, bool $authMechValidate, bool $expectSuccess): void {
		$backend = $this->getBackendMock();
		$backend->method('validateStorage')
			->willReturn($backendValidate);
		$backend->method('isVisibleFor')
			->willReturn(true);

		$authMech = $this->getAuthMechMock();
		$authMech->method('validateStorage')
			->willReturn($authMechValidate);
		$authMech->method('isVisibleFor')
			->willReturn(true);

		$storageConfig = new StorageConfig();
		$storageConfig->setMountPoint('mount');
		$storageConfig->setBackend($backend);
		$storageConfig->setAuthMechanism($authMech);
		$storageConfig->setBackendOptions([]);

		$this->service->expects($this->once())
			->method('createStorage')
			->willReturn($storageConfig);

		if ($expectSuccess) {
			$this->service->expects($this->once())
				->method('addStorage')
				->with($storageConfig)
				->willReturn($storageConfig);
		} else {
			$this->service->expects($this->never())
				->method('addStorage');
		}

		$response = $this->controller->create(
			'mount',
			\OCA\Files_External\Lib\Storage\SMB::class,
			NullMechanism::class,
			[],
			[],
			[],
			[],
			null
		);

		if ($expectSuccess) {
			$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		} else {
			$this->assertEquals(Http::STATUS_UNPROCESSABLE_ENTITY, $response->getStatus());
		}
	}
}
