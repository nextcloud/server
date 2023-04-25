<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @author Morris Jobke
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\AppFramework\DependencyInjection;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\MiddlewareRegistration;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\DependencyInjection\DIContainer;
use OC\AppFramework\Http\Request;
use OC\AppFramework\Middleware\Security\SecurityMiddleware;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\QueryException;
use OCP\IConfig;
use OCP\IRequestId;

/**
 * @group DB
 */
class DIContainerTest extends \Test\TestCase {
	/** @var DIContainer|\PHPUnit\Framework\MockObject\MockObject */
	private $container;

	protected function setUp(): void {
		parent::setUp();
		$this->container = $this->getMockBuilder(DIContainer::class)
			->setMethods(['isAdminUser'])
			->setConstructorArgs(['name'])
			->getMock();
	}


	public function testProvidesRequest() {
		$this->assertTrue(isset($this->container['Request']));
	}

	public function testProvidesMiddlewareDispatcher() {
		$this->assertTrue(isset($this->container['MiddlewareDispatcher']));
	}

	public function testProvidesAppName() {
		$this->assertTrue(isset($this->container['AppName']));
	}


	public function testAppNameIsSetCorrectly() {
		$this->assertEquals('name', $this->container['AppName']);
	}

	public function testMiddlewareDispatcherIncludesSecurityMiddleware() {
		$this->container['Request'] = new Request(
			['method' => 'GET'],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		);
		$dispatcher = $this->container['MiddlewareDispatcher'];
		$middlewares = $dispatcher->getMiddlewares();

		$found = false;
		foreach ($middlewares as $middleware) {
			if ($middleware instanceof SecurityMiddleware) {
				$found = true;
			}
		}

		$this->assertTrue($found);
	}

	public function testMiddlewareDispatcherIncludesBootstrapMiddlewares(): void {
		$coordinator = $this->createMock(Coordinator::class);
		$this->container[Coordinator::class] = $coordinator;
		$this->container['Request'] = $this->createMock(Request::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getMiddlewareRegistrations')
			->willReturn([
				new MiddlewareRegistration($this->container['appName'], 'foo', false),
				new MiddlewareRegistration('otherapp', 'bar', false),
			]);
		$this->container['foo'] = new class extends Middleware {
		};
		$this->container['bar'] = new class extends Middleware {
		};
		$coordinator->method('getRegistrationContext')->willReturn($registrationContext);

		$dispatcher = $this->container['MiddlewareDispatcher'];

		$middlewares = $dispatcher->getMiddlewares();
		self::assertNotEmpty($middlewares);
		foreach ($middlewares as $middleware) {
			if ($middleware === $this->container['bar']) {
				$this->fail('Container must not register this middleware');
			}
			if ($middleware === $this->container['foo']) {
				// It is done
				return;
			}
		}
		$this->fail('Bootstrap registered middleware not found');
	}

	public function testMiddlewareDispatcherIncludesGlobalBootstrapMiddlewares(): void {
		$coordinator = $this->createMock(Coordinator::class);
		$this->container[Coordinator::class] = $coordinator;
		$this->container['Request'] = $this->createMock(Request::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getMiddlewareRegistrations')
			->willReturn([
				new MiddlewareRegistration('otherapp', 'foo', true),
				new MiddlewareRegistration('otherapp', 'bar', false),
			]);
		$this->container['foo'] = new class extends Middleware {
		};
		$this->container['bar'] = new class extends Middleware {
		};
		$coordinator->method('getRegistrationContext')->willReturn($registrationContext);

		$dispatcher = $this->container['MiddlewareDispatcher'];

		$middlewares = $dispatcher->getMiddlewares();
		self::assertNotEmpty($middlewares);
		foreach ($middlewares as $middleware) {
			if ($middleware === $this->container['bar']) {
				$this->fail('Container must not register this middleware');
			}
			if ($middleware === $this->container['foo']) {
				// It is done
				return;
			}
		}
		$this->fail('Bootstrap registered middleware not found');
	}

	public function testInvalidAppClass() {
		$this->expectException(QueryException::class);
		$this->container->query('\OCA\Name\Foo');
	}
}
