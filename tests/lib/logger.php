<?php
/**
 * Copyright (c) 2014 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Log;

class Logger extends TestCase {
	/**
	 * @var \OCP\ILogger
	 */
	private $logger;
	static private $logs = array();

	protected function setUp() {
		parent::setUp();

		self::$logs = array();
		$this->config = $this->getMockBuilder(
			'\OC\SystemConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = new Log('Test\Logger', $this->config);
	}

	public function testInterpolation() {
		$logger = $this->logger;
		$logger->warning('{Message {nothing} {user} {foo.bar} a}', array('user' => 'Bob', 'foo.bar' => 'Bar'));

		$expected = array('2 {Message {nothing} Bob Bar a}');
		$this->assertEquals($expected, $this->getLogs());
	}

	public function testAppCondition() {
		$this->config->expects($this->any())
			->method('getValue')
			->will(($this->returnValueMap([
				['loglevel', \OC_Log::WARN, \OC_Log::WARN],
				['log.condition', [], ['apps' => ['files']]]
			])));
		$logger = $this->logger;

		$logger->info('Don\'t display info messages');
		$logger->info('Show info messages of files app', ['app' => 'files']);
		$logger->warning('Show warning messages of other apps');

		$expected = [
			'1 Show info messages of files app',
			'2 Show warning messages of other apps',
		];
		$this->assertEquals($expected, $this->getLogs());
	}

	private function getLogs() {
		return self::$logs;
	}

	public static function write($app, $message, $level) {
		self::$logs[]= "$level $message";
	}
}
