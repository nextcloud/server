<?php

declare(strict_types=1);

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
use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class DIContainerTest extends \Test\TestCase {
	private DIContainer&MockObject $container;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->container = $this->getMockBuilder(DIContainer::class)
			->onlyMethods([])
			->setConstructorArgs(['name'])
			->getMock();
	}

	public function testProvidesRequest(): void {
		$this->assertTrue($this->container->has('Request'));
	}

	public function testProvidesMiddlewareDispatcher(): void {
		$this->assertTrue($this->container->has('MiddlewareDispatcher'));
	}

	public function testProvidesAppName(): void {
		$this->assertTrue($this->container->has('AppName'));
		$this->assertTrue($this->container->has('appName'));
	}

	public function testAppNameIsSetCorrectly(): void {
		$this->assertEquals('name', $this->container->get('AppName'));
		$this->assertEquals('name', $this->container->get('appName'));
	}

	public function testMiddlewareDispatcherIncludesSecurityMiddleware(): void {
		$this->container->registerService('Request', fn () => new Request(
			['method' => 'GET'],
			$this->createMock(IRequestId::class),
			$this->createMock(IConfig::class)
		));
		$dispatcher = $this->container->get('MiddlewareDispatcher');
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
		$this->container->registerService(Coordinator::class, fn () => $coordinator);
		$this->container->registerService('Request', fn () => $this->createMock(Request::class));
		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getMiddlewareRegistrations')
			->willReturn([
				new MiddlewareRegistration($this->container->get('appName'), 'foo', false),
				new MiddlewareRegistration('otherapp', 'bar', false),
			]);
		$this->container->registerService('foo', fn () => new class extends Middleware {
		});
		$this->container->registerService('bar', fn () => new class extends Middleware {
		});
		$coordinator->method('getRegistrationContext')->willReturn($registrationContext);

		$dispatcher = $this->container->get('MiddlewareDispatcher');

		$middlewares = $dispatcher->getMiddlewares();
		self::assertNotEmpty($middlewares);
		foreach ($middlewares as $middleware) {
			if ($middleware === $this->container->get('bar')) {
				$this->fail('Container must not register this middleware');
			}
			if ($middleware === $this->container->get('foo')) {
				// It is done
				return;
			}
		}
		$this->fail('Bootstrap registered middleware not found');
	}

	public function testMiddlewareDispatcherIncludesGlobalBootstrapMiddlewares(): void {
		$coordinator = $this->createMock(Coordinator::class);
		$this->container->registerService(Coordinator::class, fn () => $coordinator);
		$this->container->registerService('Request', fn () => $this->createMock(Request::class));
		$registrationContext = $this->createMock(RegistrationContext::class);
		$registrationContext->method('getMiddlewareRegistrations')
			->willReturn([
				new MiddlewareRegistration('otherapp', 'foo', true),
				new MiddlewareRegistration('otherapp', 'bar', false),
			]);
		$this->container->registerService('foo', fn () => new class extends Middleware {
		});
		$this->container->registerService('bar', fn () => new class extends Middleware {
		});
		$coordinator->method('getRegistrationContext')->willReturn($registrationContext);

		$dispatcher = $this->container->get('MiddlewareDispatcher');

		$middlewares = $dispatcher->getMiddlewares();
		self::assertNotEmpty($middlewares);
		foreach ($middlewares as $middleware) {
			if ($middleware === $this->container->get('bar')) {
				$this->fail('Container must not register this middleware');
			}
			if ($middleware === $this->container->get('foo')) {
				// It is done
				return;
			}
		}
		$this->fail('Bootstrap registered middleware not found');
	}

	public function testInvalidAppClass(): void {
		$this->expectException(QueryException::class);
		$this->container->get('\OCA\Name\Foo');
	}
}
