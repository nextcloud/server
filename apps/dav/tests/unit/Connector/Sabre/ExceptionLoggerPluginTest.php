<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OC\Log;
use OC\SystemConfig;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\Server;
use Test\TestCase;

class ExceptionLoggerPluginTest extends TestCase {

	/** @var Server */
	private $server;

	/** @var ExceptionLoggerPlugin */
	private $plugin;

	/** @var LoggerInterface | \PHPUnit\Framework\MockObject\MockObject */
	private $logger;

	private function init() {
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
	public function testLogging(string $expectedLogLevel, \Throwable $e) {
		$this->init();

		$this->logger->expects($this->once())
			->method($expectedLogLevel)
			->with($e->getMessage(), ['app' => 'unit-test','exception' => $e]);

		$this->plugin->logException($e);
	}

	public function providesExceptions() {
		return [
			['debug', new NotFound()],
			['debug', new ServiceUnavailable('System in maintenance mode.')],
			['critical', new ServiceUnavailable('Upgrade needed')],
			['critical', new InvalidPath('This path leads to nowhere')]
		];
	}
}
