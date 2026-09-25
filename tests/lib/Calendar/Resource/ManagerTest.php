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
use Psr\Container\ContainerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Manager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createInstanceWithMocks(Manager::class);
	}

	public function testGetBackendFromBootstrapRegistration(): void {
		$backendClass = '\\OCA\\CalendarResourceFoo\\Backend';
		$backend = $this->createMock(IBackend::class);
		$backend->method('getBackendIdentifier')->willReturn('from_bootstrap');
		$context = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($context);
		$context->expects(self::once())
			->method('getCalendarResourceBackendRegistrations')
			->willReturn([
				new ServiceRegistration('calendar_resource_foo', $backendClass)
			]);
		$this->mocks[ContainerInterface::class]->expects(self::once())
			->method('get')
			->with($backendClass)
			->willReturn($backend);

		self::assertEquals($backend, $this->manager->getBackend('from_bootstrap'));
	}

	public function testUpdate(): void {
		$this->mocks[ResourcesRoomsUpdater::class]->expects(self::once())
			->method('updateResources');

		$this->manager->update();
	}
}
