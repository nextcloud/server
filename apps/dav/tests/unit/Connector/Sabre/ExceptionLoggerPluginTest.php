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

use Exception;
use OC\SystemConfig;
use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin as PluginToTest;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\ServiceUnavailable;
use Sabre\DAV\Server;
use Test\TestCase;

class TestLogger extends AbstractLogger {
	/** @var string $message */
	public $message;
	/** @var int $level */
	public $level;
	/** @var array $context */
	public $context;

	public function log($level, $message, array $context = array()) {
		$this->level = $level;
		$this->message = $message;
		$this->context = $context;
	}
}

class ExceptionLoggerPluginTest extends TestCase {

	/** @var PluginToTest */
	private $plugin;

	/** @var TestLogger | MockObject */
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

		$server = new Server();
		$this->logger = new TestLogger();
		$this->plugin = new PluginToTest('unit-test', $this->logger);
		$this->plugin->initialize($server);
	}

	/**
	 * @dataProvider providesExceptions
	 */
	public function testLogging(string $expectedLogLevel, string $expectedMessage, Exception $exception) {
		$this->init();
		$this->plugin->logException($exception);

		$this->assertEquals($expectedLogLevel, $this->logger->level);
		$this->assertInstanceOf(get_class($exception), $this->logger->context['exception']);
		$this->assertEquals($expectedMessage, $this->logger->message);
	}

	public function providesExceptions(): array {
		return [
			[LogLevel::DEBUG, '', new NotFound()],
			[LogLevel::DEBUG, 'System in maintenance mode.', new ServiceUnavailable('System in maintenance mode.')],
			[LogLevel::CRITICAL, 'Upgrade needed', new ServiceUnavailable('Upgrade needed')],
			[LogLevel::CRITICAL, 'This path leads to nowhere', new InvalidPath('This path leads to nowhere')]
		];
	}
}
