<?php
/**
 * Copyright (c) 2014 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Log;

class Logger extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OCP\ILogger
	 */
	private $logger;
	static private $logs = array();

	public function setUp() {
		self::$logs = array();
		$this->logger = new Log('Test\Logger');
	}

	public function testInterpolation() {
		$logger = $this->logger;
		$logger->info('{Message {nothing} {user} {foo.bar} a}', array('user' => 'Bob', 'foo.bar' => 'Bar'));

		$expected = array('1 {Message {nothing} Bob Bar a}');
		$this->assertEquals($expected, $this->getLogs());
	}

	private function getLogs() {
		return self::$logs;
	}

	public static function write($app, $message, $level) {
		self::$logs[]= "$level $message";
	}
}
