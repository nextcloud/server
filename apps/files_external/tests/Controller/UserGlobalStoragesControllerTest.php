<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Controller;

use OCA\Files_External\Controller\UserGlobalStoragesController;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\Password\UserProvided;
use OCA\Files_External\Lib\Backend\Backend;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCA\Files_External\Service\MountCacheService;
use OCA\Files_External\Service\UserGlobalStoragesService;
use OCP\AppFramework\Http;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class UserGlobalStoragesControllerTest extends TestCase {
	private UserGlobalStoragesService&MockObject $service;
	private IUserSession&MockObject $userSession;
	private IEventDispatcher&MockObject $eventDispatcher;
	private MountCacheService&MockObject $mountCacheService;
	private UserGlobalStoragesController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->createMock(UserGlobalStoragesService::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userSession->method('getUser')->willReturn($this->createMock(IUser::class));
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->mountCacheService = $this->createMock(MountCacheService::class);

		$this->controller = new UserGlobalStoragesController(
			'files_external',
			$this->createMock(IRequest::class),
			$this->createMock(IL10N::class),
			$this->service,
			$this->createMock(LoggerInterface::class),
			$this->userSession,
			$this->createMock(IGroupManager::class),
			$this->createMock(IConfig::class),
			$this->createMock(BackendService::class),
			$this->eventDispatcher,
			$this->mountCacheService,
		);
	}

	private function getStorage(AuthMechanism $authMechanism): StorageConfig {
		$storage = new StorageConfig(42);
		$storage->setMountPoint('mount');
		$backend = $this->createMock(Backend::class);
		$backend->method('getStorageClass')->willReturn('\\OC\\Files\\Storage\\Local');
		$storage->setBackend($backend);
		$storage->setAuthMechanism($authMechanism);
		return $storage;
	}

	public function testUpdateUserProvidedCredentials(): void {
		$authMechanism = $this->createMock(UserProvided::class);
		$authMechanism->expects($this->once())
			->method('saveBackendOptions')
			->with($this->anything(), 42, ['user' => 'u', 'password' => 'p']);
		$storage = $this->getStorage($authMechanism);
		$this->service->method('getStorage')
			->with(42)
			->willReturn($storage);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'files_external.user_credentials.updated'
				&& $event->getParameters() === ['storageId' => 42]
				&& $event->getLogMessage() === 'User credentials for external storage "%s" were updated'));

		$this->mountCacheService->expects($this->once())
			->method('handleUpdatedCredentials')
			->with($this->userSession->getUser(), $storage);

		$response = $this->controller->update(42, ['user' => 'u', 'password' => 'p']);
		$this->assertSame(Http::STATUS_OK, $response->getStatus());
	}

	public function testUpdateNotUserProvided(): void {
		$this->service->method('getStorage')
			->with(42)
			->willReturn($this->getStorage($this->createMock(AuthMechanism::class)));

		$this->eventDispatcher->expects($this->never())
			->method('dispatchTyped');
		$this->mountCacheService->expects($this->never())
			->method('handleUpdatedCredentials');

		$response = $this->controller->update(42, ['user' => 'u', 'password' => 'p']);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}
}
