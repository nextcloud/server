<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Route;

use OC\App\AppManager;
use OC\Route\Router;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Test\TestCase;

/**
 * Class RouterTest
 *
 * @group RoutingWeirdness
 *
 * @package Test\Route
 */
class RouterTest extends TestCase {
	private Router $router;
	protected function setUp(): void {
		parent::setUp();
		/** @var LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('info')
			->willReturnCallback(
				function (string $message, array $data) {
					$this->fail('Unexpected info log: '.(string)($data['exception'] ?? $message));
				}
			);

		/**
		 * The router needs to resolve an app id to an app path.
		 * A non-mocked AppManager instance is required.
		 */
		$appManager = new AppManager(
			$this->createMock(IUserSession::class),
			$this->createMock(IConfig::class),
			$this->createMock(IGroupManager::class),
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class),
			new NullLogger(),
		);

		$this->router = new Router(
			$logger,
			$this->createMock(IRequest::class),
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$appManager,
		);
	}

	public function testHeartbeat(): void {
		$this->assertEquals('/index.php/heartbeat', $this->router->generate('heartbeat'));
	}

	public function testGenerateConsecutively(): void {

		$this->assertEquals('/index.php/apps/files/', $this->router->generate('files.view.index'));

		// the OCS route is the prefixed one for the AppFramework - see /ocs/v1.php for routing details
		$this->assertEquals('/index.php/ocsapp/apps/dav/api/v1/direct', $this->router->generate('ocs.dav.direct.getUrl'));

		// test caching
		$this->assertEquals('/index.php/apps/files/', $this->router->generate('files.view.index'));
	}
}
