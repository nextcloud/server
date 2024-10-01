<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Core\Middleware\TwoFactorMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RegistrationContextTest extends TestCase {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var RegistrationContext */
	private $context;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->context = new RegistrationContext(
			$this->logger
		);
	}

	public function testRegisterCapability(): void {
		$app = $this->createMock(App::class);
		$name = 'abc';
		$container = $this->createMock(IAppContainer::class);
		$app->method('getContainer')
			->willReturn($container);
		$container->expects($this->once())
			->method('registerCapability')
			->with($name);
		$this->logger->expects($this->never())
			->method('error');

		$this->context->for('myapp')->registerCapability($name);
		$this->context->delegateCapabilityRegistrations([
			'myapp' => $app,
		]);
	}

	public function testRegisterEventListener(): void {
		$event = 'abc';
		$service = 'def';
		$dispatcher = $this->createMock(IEventDispatcher::class);
		$dispatcher->expects($this->once())
			->method('addServiceListener')
			->with($event, $service, 0);
		$this->logger->expects($this->never())
			->method('error');

		$this->context->for('myapp')->registerEventListener($event, $service);
		$this->context->delegateEventListenerRegistrations($dispatcher);
	}

	/**
	 * @dataProvider dataProvider_TrueFalse
	 */
	public function testRegisterService(bool $shared): void {
		$app = $this->createMock(App::class);
		$service = 'abc';
		$factory = function () {
			return 'def';
		};
		$container = $this->createMock(IAppContainer::class);
		$app->method('getContainer')
			->willReturn($container);
		$container->expects($this->once())
			->method('registerService')
			->with($service, $factory, $shared);
		$this->logger->expects($this->never())
			->method('error');

		$this->context->for('myapp')->registerService($service, $factory, $shared);
		$this->context->delegateContainerRegistrations([
			'myapp' => $app,
		]);
	}

	public function testRegisterServiceAlias(): void {
		$app = $this->createMock(App::class);
		$alias = 'abc';
		$target = 'def';
		$container = $this->createMock(IAppContainer::class);
		$app->method('getContainer')
			->willReturn($container);
		$container->expects($this->once())
			->method('registerAlias')
			->with($alias, $target);
		$this->logger->expects($this->never())
			->method('error');

		$this->context->for('myapp')->registerServiceAlias($alias, $target);
		$this->context->delegateContainerRegistrations([
			'myapp' => $app,
		]);
	}

	public function testRegisterParameter(): void {
		$app = $this->createMock(App::class);
		$name = 'abc';
		$value = 'def';
		$container = $this->createMock(IAppContainer::class);
		$app->method('getContainer')
			->willReturn($container);
		$container->expects($this->once())
			->method('registerParameter')
			->with($name, $value);
		$this->logger->expects($this->never())
			->method('error');

		$this->context->for('myapp')->registerParameter($name, $value);
		$this->context->delegateContainerRegistrations([
			'myapp' => $app,
		]);
	}

	public function testRegisterUserMigrator(): void {
		$appIdA = 'myapp';
		$migratorClassA = 'OCA\App\UserMigration\AppMigrator';

		$appIdB = 'otherapp';
		$migratorClassB = 'OCA\OtherApp\UserMigration\OtherAppMigrator';

		$serviceRegistrationA = new ServiceRegistration($appIdA, $migratorClassA);
		$serviceRegistrationB = new ServiceRegistration($appIdB, $migratorClassB);

		$this->context
			->for($appIdA)
			->registerUserMigrator($migratorClassA);
		$this->context
			->for($appIdB)
			->registerUserMigrator($migratorClassB);

		$this->assertEquals(
			[
				$serviceRegistrationA,
				$serviceRegistrationB,
			],
			$this->context->getUserMigrators(),
		);
	}

	public function dataProvider_TrueFalse() {
		return[
			[true],
			[false]
		];
	}

	public function testGetMiddlewareRegistrations(): void {
		$this->context->registerMiddleware('core', TwoFactorMiddleware::class, false);

		$registrations = $this->context->getMiddlewareRegistrations();

		self::assertNotEmpty($registrations);
		self::assertSame('core', $registrations[0]->getAppId());
		self::assertSame(TwoFactorMiddleware::class, $registrations[0]->getService());
	}
}
