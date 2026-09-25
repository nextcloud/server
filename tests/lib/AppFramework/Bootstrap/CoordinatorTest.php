<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\AppFramework\Bootstrap;

use OC\App\AppManager;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Server;
use OCA\Settings\AppInfo\Application;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\QueryException;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CoordinatorTest extends TestCase {
	private Coordinator $coordinator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createInstanceWithMocks(Coordinator::class);
		$this->mocks[AppManager::class]->expects($this->any())
			->method('getAppNamespace')
			->with('settings')
			->willReturn('OCA\\Settings');
	}

	public function testBootAppNotLoadable(): void {
		$appId = 'settings';
		$this->mocks[Server::class]->expects($this->once())
			->method('get')
			->with(Application::class)
			->willThrowException(new QueryException(''));
		$this->mocks[LoggerInterface::class]->expects($this->once())
			->method('error');

		$this->coordinator->bootApp($appId);
	}

	public function testBootAppNotBootable(): void {
		$appId = 'settings';
		$mockApp = $this->createMock(Application::class);
		$this->mocks[Server::class]->expects($this->once())
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
		$this->mocks[Server::class]->expects($this->once())
			->method('get')
			->with(Application::class)
			->willReturn($mockApp);

		$this->coordinator->bootApp($appId);
	}
}
