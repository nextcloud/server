<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Route;

use OC\Route\Router;
use OCP\App\IAppManager;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
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
		$this->router = new Router(
			$logger,
			$this->createMock(IRequest::class),
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
			$this->createMock(IAppManager::class),
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
