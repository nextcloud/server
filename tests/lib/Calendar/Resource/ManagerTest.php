<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Calendar\Resource;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Calendar\Resource\Manager;
use OCP\Calendar\Resource\IBackend;
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ManagerTest extends TestCase {

	/** @var Coordinator|MockObject */
	private $coordinator;

	/** @var IServerContainer|MockObject */
	private $server;

	/** @var Manager */
	private $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->server = $this->createMock(IServerContainer::class);
		$this->manager = new Manager(
			$this->coordinator,
			$this->server,
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
}
