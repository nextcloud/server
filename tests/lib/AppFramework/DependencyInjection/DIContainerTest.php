<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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


	public function testProvidesRequest(): void {
		$this->assertTrue(isset($this->container['Request']));
	}

	public function testProvidesMiddlewareDispatcher(): void {
		$this->assertTrue(isset($this->container['MiddlewareDispatcher']));
	}

	public function testProvidesAppName(): void {
		$this->assertTrue(isset($this->container['AppName']));
	}


	public function testAppNameIsSetCorrectly(): void {
		$this->assertEquals('name', $this->container['AppName']);
	}

	public function testMiddlewareDispatcherIncludesSecurityMiddleware(): void {
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

	public function testInvalidAppClass(): void {
		$this->expectException(QueryException::class);
		$this->container->query('\OCA\Name\Foo');
	}
}
