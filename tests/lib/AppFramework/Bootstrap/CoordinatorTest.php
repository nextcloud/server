<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\App\AppManager;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Utility\PersistentServiceInvalidator;
use OC\AppFramework\Utility\SimpleContainer;
use OC\Support\CrashReport\Registry;
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\QueryException;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\Dashboard\IManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CoordinatorTest extends TestCase {
	private AppManager&MockObject $appManager;
	private ContainerInterface&MockObject $serverContainer;
	private Registry&MockObject $crashReporterRegistry;
	private IManager&MockObject $dashboardManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IEventLogger&MockObject $eventLogger;
	private LoggerInterface&MockObject $logger;
	private PersistentServiceInvalidator&MockObject $persistentServiceInvalidator;
	private Coordinator $coordinator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(AppManager::class);
		$this->serverContainer = $this->createMock(ContainerInterface::class);
		$this->crashReporterRegistry = $this->createMock(Registry::class);
		$this->dashboardManager = $this->createMock(IManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->persistentServiceInvalidator = $this->createMock(PersistentServiceInvalidator::class);

		$this->appManager->expects($this->any())
			->method('getAppNamespace')
			->with('settings')
			->willReturn('OCA\\Settings');

		$this->coordinator = new Coordinator(
			$this->serverContainer,
			$this->crashReporterRegistry,
			$this->dashboardManager,
			$this->eventDispatcher,
			$this->eventLogger,
			$this->appManager,
			$this->logger,
			$this->persistentServiceInvalidator,
		);
	}

	#[\Override]
	protected function tearDown(): void {
		SimpleContainer::resetPersistentInstances();
		Coordinator::resetPersistentRegistrations();

		parent::tearDown();
	}

	public function testBootAppNotLoadable(): void {
		$appId = 'settings';
		$this->serverContainer->expects($this->once())
			->method('get')
			->with(Application::class)
			->willThrowException(new QueryException(''));
		$this->logger->expects($this->once())
			->method('error');

		$this->coordinator->bootApp($appId);
	}

	public function testBootAppNotBootable(): void {
		$appId = 'settings';
		$mockApp = $this->createMock(Application::class);
		$this->serverContainer->expects($this->once())
			->method('get')
			->with(Application::class)
			->willReturn($mockApp);

		$this->coordinator->bootApp($appId);
	}

	public function testBootApp(): void {
		$appId = 'settings';
		$mockApp = new class extends App implements IBootstrap {
			public function __construct() {
				parent::__construct('test', []);
			}

			#[\Override]
			public function register(IRegistrationContext $context): void {
			}

			#[\Override]
			public function boot(IBootContext $context): void {
			}
		};
		$this->serverContainer->expects($this->once())
			->method('get')
			->with(Application::class)
			->willReturn($mockApp);

		$this->coordinator->bootApp($appId);
	}

	private function makeCountingApp(\stdClass $counter): App&IBootstrap {
		return new class($counter) extends App implements IBootstrap {
			public function __construct(
				private \stdClass $counter,
			) {
				parent::__construct('settings', []);
			}

			#[\Override]
			public function register(IRegistrationContext $context): void {
				$this->counter->registerCalls++;
			}

			#[\Override]
			public function boot(IBootContext $context): void {
			}
		};
	}

	public function testRegisterAppsOnlyRegistersOnceWhilePersistent(): void {
		SimpleContainer::$keepPersistentServices = true;
		$this->persistentServiceInvalidator->method('getGeneration')
			->with(PersistentServiceGroup::Apps)
			->willReturn(1);

		$counter = new \stdClass();
		$counter->registerCalls = 0;
		$this->serverContainer->method('get')
			->with(Application::class)
			->willReturn($this->makeCountingApp($counter));

		$this->coordinator->runLazyRegistration('settings');
		$this->coordinator->runLazyRegistration('settings');

		$this->assertSame(1, $counter->registerCalls);
	}

	public function testRegisterAppsRunsAgainAfterAppsGenerationChanges(): void {
		SimpleContainer::$keepPersistentServices = true;

		$counter = new \stdClass();
		$counter->registerCalls = 0;
		$this->serverContainer->method('get')
			->with(Application::class)
			->willReturn($this->makeCountingApp($counter));

		$generation = 1;
		$this->persistentServiceInvalidator->method('getGeneration')
			->with(PersistentServiceGroup::Apps)
			->willReturnCallback(function () use (&$generation) {
				return $generation;
			});

		$this->coordinator->runLazyRegistration('settings');
		$generation = 2;
		$this->coordinator->runLazyRegistration('settings');

		$this->assertSame(2, $counter->registerCalls);
	}
}
