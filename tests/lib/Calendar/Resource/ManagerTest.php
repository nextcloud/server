<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Calendar\Resource;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Calendar\Resource\Manager;
use OC\Calendar\ResourcesRoomsUpdater;
use OCP\Calendar\Resource\IBackend;
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var Coordinator|MockObject */
	private $coordinator;

	/** @var IServerContainer|MockObject */
	private $server;

	/** @var ResourcesRoomsUpdater|MockObject */
	private $resourcesRoomsUpdater;

	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->server = $this->createMock(IServerContainer::class);
		$this->resourcesRoomsUpdater = $this->createMock(ResourcesRoomsUpdater::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->server,
			$this->resourcesRoomsUpdater,
		);
	}

	public function testRegisterUnregisterBackend(): void {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->willReturn('backend_1');
		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->willReturn('backend_2');
		$this->server->expects(self::exactly(2))
			->method('query')
			->willReturnMap([
				['calendar_resource_backend1', true, $backend1,],
				['calendar_resource_backend2', true, $backend2,],
			]);

		$this->manager->registerBackend('calendar_resource_backend1');
		$this->manager->registerBackend('calendar_resource_backend2');

		self::assertEquals([
			$backend1, $backend2
		], $this->manager->getBackends());
		$this->manager->unregisterBackend('calendar_resource_backend1');
		self::assertEquals([
			$backend2
		], $this->manager->getBackends());
	}

	public function testGetBackendFromBootstrapRegistration(): void {
		$backendClass = '\\OCA\\CalendarResourceFoo\\Backend';
		$backend = $this->createMock(IBackend::class);
		$backend->method('getBackendIdentifier')->willReturn('from_bootstrap');
		$context = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($context);
		$context->expects(self::once())
			->method('getCalendarResourceBackendRegistrations')
			->willReturn([
				new ServiceRegistration('calendar_resource_foo', $backendClass)
			]);
		$this->server->expects(self::once())
			->method('query')
			->with($backendClass)
			->willReturn($backend);

		self::assertEquals($backend, $this->manager->getBackend('from_bootstrap'));
	}

	public function testGetBackend(): void {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->willReturn('backend_1');
		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->willReturn('backend_2');
		$this->server->expects(self::exactly(2))
			->method('query')
			->willReturnMap([
				['calendar_resource_backend1', true, $backend1,],
				['calendar_resource_backend2', true, $backend2,],
			]);

		$this->manager->registerBackend('calendar_resource_backend1');
		$this->manager->registerBackend('calendar_resource_backend2');

		self::assertEquals($backend1, $this->manager->getBackend('backend_1'));
		self::assertEquals($backend2, $this->manager->getBackend('backend_2'));
	}

	public function testClear(): void {
		$backend1 = $this->createMock(IBackend::class);
		$backend1->method('getBackendIdentifier')->willReturn('backend_1');
		$backend2 = $this->createMock(IBackend::class);
		$backend2->method('getBackendIdentifier')->willReturn('backend_2');
		$this->server->expects(self::exactly(2))
			->method('query')
			->willReturnMap([
				['calendar_resource_backend1', true, $backend1,],
				['calendar_resource_backend2', true, $backend2,],
			]);

		$this->manager->registerBackend('calendar_resource_backend1');
		$this->manager->registerBackend('calendar_resource_backend2');

		self::assertEquals([
			$backend1, $backend2
		], $this->manager->getBackends());

		$this->manager->clear();

		self::assertEquals([], $this->manager->getBackends());
	}

	public function testUpdate(): void {
		$this->resourcesRoomsUpdater->expects(self::once())
			->method('updateResources');

		$this->manager->update();
	}
}
