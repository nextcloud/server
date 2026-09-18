<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Route;

use OC\Route\Router;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class RouterTest
 *
 *
 * @package Test\Route
 */
#[\PHPUnit\Framework\Attributes\Group('RoutingWeirdness')]
class RouterTest extends TestCase {
	private Router $router;
	private IAppManager&MockObject $appManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		/** @var LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('info')
			->willReturnCallback(
				function (string $message, array $data): void {
					$this->fail('Unexpected info log: ' . (string)($data['exception'] ?? $message));
				}
			);

		$this->appManager = $this->createMock(IAppManager::class);

		$this->router = new Router(
			$logger,
			$this->createMock(IRequest::class),
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$this->appManager,
		);
	}

	public function testHeartbeat(): void {
		$this->assertEquals('/index.php/heartbeat', $this->router->generate('heartbeat'));
	}

	public function testRefreshContextUpdatesGeneratedAbsoluteUrls(): void {
		$firstRequest = $this->createMock(IRequest::class);
		$firstRequest->method('getServerHost')->willReturn('first.example.com');
		$firstRequest->method('getServerProtocol')->willReturn('http');

		$router = new Router(
			$this->createMock(LoggerInterface::class),
			$firstRequest,
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$this->appManager,
		);

		$this->assertSame('http://first.example.com/index.php/heartbeat', $router->generate('heartbeat', [], true));

		$secondRequest = $this->createMock(IRequest::class);
		$secondRequest->method('getServerHost')->willReturn('second.example.com');
		$secondRequest->method('getServerProtocol')->willReturn('https');
		$router->refreshContext($secondRequest);

		$this->assertSame('https://second.example.com/index.php/heartbeat', $router->generate('heartbeat', [], true));
	}

	public function testLoadRoutesForAppChecksIsEnabledForAnyoneNotPerUser(): void {
		// $root is shared across every request a persisted Router serves, so gating it by the
		// current user (rather than system-wide enablement) would leak into other users' requests.
		$this->appManager->method('cleanAppId')->willReturnArgument(0);
		$this->appManager->method('getAppPath')->willThrowException(new AppPathNotFoundException());
		$this->appManager->expects(self::once())
			->method('isEnabledForAnyone')
			->with('some_app')
			->willReturn(false);
		$this->appManager->expects(self::never())
			->method('isEnabledForUser');

		$this->router->loadRoutes('some_app', skipLoadingCore: true);
	}

	public function testRefreshRequestScopedCollaboratorsUpdatesAppManager(): void {
		$firstAppManager = $this->createMock(IAppManager::class);
		$firstAppManager->method('cleanAppId')->willReturnArgument(0);
		$firstAppManager->method('getAppPath')->willThrowException(new AppPathNotFoundException());
		$firstAppManager->method('isEnabledForAnyone')->willReturn(false);

		$router = new Router(
			$this->createMock(LoggerInterface::class),
			$this->createMock(IRequest::class),
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$firstAppManager,
		);

		$secondAppManager = $this->createMock(IAppManager::class);
		$secondAppManager->method('cleanAppId')->willReturnArgument(0);
		$secondAppManager->expects(self::once())
			->method('getAppPath')
			->willThrowException(new AppPathNotFoundException());
		$secondAppManager->expects(self::once())
			->method('isEnabledForAnyone')
			->willReturn(false);
		$router->refreshRequestScopedCollaborators($secondAppManager, $this->createMock(IEventLogger::class));

		$router->loadRoutes('some_app', skipLoadingCore: true);
	}

	public function testGenerateConsecutively(): void {
		$this->appManager->expects(self::atLeastOnce())
			->method('cleanAppId')
			->willReturnArgument(0);
		$this->appManager->expects(self::atLeastOnce())
			->method('getAppPath')
			->willReturnCallback(fn (string $appid): string => \OC::$SERVERROOT . '/apps/' . $appid);
		$this->appManager->expects(self::atLeastOnce())
			->method('isAppLoaded')
			->willReturn(true);

		$this->assertEquals('/index.php/apps/files/', $this->router->generate('files.view.index'));

		// the OCS route is the prefixed one for the AppFramework - see /ocs/v1.php for routing details
		$this->assertEquals('/index.php/ocsapp/apps/dav/api/v1/direct', $this->router->generate('ocs.dav.direct.getUrl'));

		// test caching
		$this->assertEquals('/index.php/apps/files/', $this->router->generate('files.view.index'));
	}
}
