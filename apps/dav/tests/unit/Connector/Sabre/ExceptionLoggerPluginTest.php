<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\SystemConfig;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Exception\ServerMaintenanceMode;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Test\TestCase;

class ExceptionLoggerPluginTest extends TestCase {
	private Server $server;
	private ExceptionLoggerPlugin $plugin;
	private LoggerInterface&MockObject $logger;

	private function init(): void {
		$config = $this->createMock(SystemConfig::class);
		$config->expects($this->any())
			->method('getValue')
			->willReturnCallback(function ($key, $default) {
				switch ($key) {
					case 'loglevel':
						return 0;
					default:
						return $default;
				}
			});

		$this->server = new Server();
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->plugin = new ExceptionLoggerPlugin('unit-test', $this->logger);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider providesExceptions
	 */
	public function testLogging(string $expectedLogLevel, \Throwable $e): void {
		$this->init();

		$this->logger->expects($this->once())
			->method($expectedLogLevel)
			->with($e->getMessage(), ['app' => 'unit-test','exception' => $e]);

		$this->plugin->logException($e);
	}

	public static function providesExceptions(): array {
		return [
			['debug', new NotFound()],
			['debug', new ServerMaintenanceMode('System is in maintenance mode.')],
			// Faking a translation
			['debug', new ServerMaintenanceMode('Syst3m 1s 1n m41nt3n4nc3 m0d3.')],
			['debug', new ServerMaintenanceMode('Upgrade needed')],
			['critical', new InvalidPath('This path leads to nowhere')]
		];
	}
}
