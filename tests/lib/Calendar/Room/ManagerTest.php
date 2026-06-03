<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Calendar\Room;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Calendar\ResourcesRoomsUpdater;
use OC\Calendar\Room\Manager;
use OCP\Calendar\Room\IBackend;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	private Coordinator&MockObject $coordinator;
	private ContainerInterface&MockObject $server;
	private ResourcesRoomsUpdater&MockObject $resourcesRoomsUpdater;
	private Manager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->server = $this->createMock(ContainerInterface::class);
		$this->resourcesRoomsUpdater = $this->createMock(ResourcesRoomsUpdater::class);

		$this->manager = new Manager(
			$this->coordinator,
			$this->server,
			$this->resourcesRoomsUpdater,
		);
	}

	public function testGetBackendFromBootstrapRegistration(): void {
		$backendClass = '\\OCA\\CalendarRoomFoo\\Backend';
		$backend = $this->createMock(IBackend::class);
		$backend->method('getBackendIdentifier')->willReturn('from_bootstrap');
		$context = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($context);
		$context->expects(self::once())
			->method('getCalendarRoomBackendRegistrations')
			->willReturn([
				new ServiceRegistration('calendar_room_foo', $backendClass)
			]);
		$this->server->expects(self::once())
			->method('get')
			->with($backendClass)
			->willReturn($backend);

		self::assertEquals($backend, $this->manager->getBackend('from_bootstrap'));
	}

	public function testUpdate(): void {
		$this->resourcesRoomsUpdater->expects(self::once())
			->method('updateRooms');

		$this->manager->update();
	}
}
