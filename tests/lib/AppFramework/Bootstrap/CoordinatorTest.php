<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Support\CrashReport\Registry;
use OCA\Settings\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\QueryException;
use OCP\Dashboard\IManager;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CoordinatorTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private IServerContainer&MockObject $serverContainer;
	private Registry&MockObject $crashReporterRegistry;
	private IManager&MockObject $dashboardManager;
	private IEventDispatcher&MockObject $eventDispatcher;
	private IEventLogger&MockObject $eventLogger;
	private LoggerInterface&MockObject $logger;
	private Coordinator $coordinator;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->crashReporterRegistry = $this->createMock(Registry::class);
		$this->dashboardManager = $this->createMock(IManager::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->eventLogger = $this->createMock(IEventLogger::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->coordinator = new Coordinator(
			$this->serverContainer,
			$this->crashReporterRegistry,
			$this->dashboardManager,
			$this->eventDispatcher,
			$this->eventLogger,
			$this->appManager,
			$this->logger,
		);
	}

	public function testBootAppNotLoadable(): void {
		$appId = 'settings';
		$this->serverContainer->expects($this->once())
			->method('query')
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
			->method('query')
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

			public function register(IRegistrationContext $context): void {
			}

			public function boot(IBootContext $context): void {
			}
		};
		$this->serverContainer->expects($this->once())
			->method('query')
			->with(Application::class)
			->willReturn($mockApp);

		$this->coordinator->bootApp($appId);
	}
}
