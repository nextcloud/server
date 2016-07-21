<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\InvalidPath;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin as PluginToTest;
use OC\Log;
use OCP\ILogger;
use PHPUnit_Framework_MockObject_MockObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Test\TestCase;

class TestLogger extends Log {
	public $message;
	public $level;

	public function __construct($logger = null) {
		//disable original constructor
	}

	public function log($level, $message, array $context = array()) {
		$this->level = $level;
		$this->message = $message;
	}
}

class ExceptionLoggerPluginTest extends TestCase {

	/** @var Server */
	private $server;

	/** @var PluginToTest */
	private $plugin;

	/** @var TestLogger | PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	private function init() {
		$this->server = new Server();
		$this->logger = new TestLogger();
		$this->plugin = new PluginToTest('unit-test', $this->logger);
		$this->plugin->initialize($this->server);
	}

	/**
	 * @dataProvider providesExceptions
	 */
	public function testLogging($expectedLogLevel, $expectedMessage, $exception) {
		$this->init();
		$this->plugin->logException($exception);

		$this->assertEquals($expectedLogLevel, $this->logger->level);
		$this->assertStringStartsWith('Exception: {"Message":"' . $expectedMessage, $this->logger->message);
	}

	public function providesExceptions() {
		return [
			[0, 'HTTP\/1.1 404 Not Found', new NotFound()],
			[4, 'HTTP\/1.1 400 This path leads to nowhere', new InvalidPath('This path leads to nowhere')]
		];
	}

}
