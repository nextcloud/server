<?php

/**
 * Copyright (c) 2015 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Connector\Sabre;

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

class ExceptionLoggerPlugin extends TestCase {

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
