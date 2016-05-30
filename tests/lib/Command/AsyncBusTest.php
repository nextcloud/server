<?php

/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Command;

use OC\Command\FileAccess;
use OCP\Command\IBus;
use OCP\Command\ICommand;
use Test\BackgroundJob\DummyJobList;
use Test\TestCase;

class SimpleCommand implements ICommand {
	public function handle() {
		AsyncBusTest::$lastCommand = 'SimpleCommand';
	}
}

class StateFullCommand implements ICommand {
	private $state;

	function __construct($state) {
		$this->state = $state;
	}

	public function handle() {
		AsyncBusTest::$lastCommand = $this->state;
	}
}

class FilesystemCommand implements ICommand {
	use FileAccess;

	public function handle() {
		AsyncBusTest::$lastCommand = 'FileAccess';
	}
}

function basicFunction() {
	AsyncBusTest::$lastCommand = 'function';
}

// clean class to prevent phpunit putting closure in $this
class ThisClosureTest {
	private function privateMethod() {
		AsyncBusTest::$lastCommand = 'closure-this';
	}

	public function test(IBus $bus) {
		$bus->push(function () {
			$this->privateMethod();
		});
	}
}

class AsyncBusTest extends TestCase {
	/**
	 * Basic way to check output from a command
	 *
	 * @var string
	 */
	public static $lastCommand;

	/**
	 * @var \OCP\BackgroundJob\IJobList
	 */
	private $jobList;

	/**
	 * @var \OCP\Command\IBus
	 */
	private $bus;

	public static function DummyCommand() {
		self::$lastCommand = 'static';
	}

	public function setUp() {
		$this->jobList = new DummyJobList();
		$this->bus = new \OC\Command\AsyncBus($this->jobList);
		self::$lastCommand = '';
	}

	public function testSimpleCommand() {
		$command = new SimpleCommand();
		$this->bus->push($command);
		$this->runJobs();
		$this->assertEquals('SimpleCommand', self::$lastCommand);
	}

	public function testStateFullCommand() {
		$command = new StateFullCommand('foo');
		$this->bus->push($command);
		$this->runJobs();
		$this->assertEquals('foo', self::$lastCommand);
	}

	public function testStaticCallable() {
		$this->bus->push(['\Test\Command\AsyncBusTest', 'DummyCommand']);
		$this->runJobs();
		$this->assertEquals('static', self::$lastCommand);
	}

	public function testMemberCallable() {
		$command = new StateFullCommand('bar');
		$this->bus->push([$command, 'handle']);
		$this->runJobs();
		$this->assertEquals('bar', self::$lastCommand);
	}

	public function testFunctionCallable() {
		$this->bus->push('\Test\Command\BasicFunction');
		$this->runJobs();
		$this->assertEquals('function', self::$lastCommand);
	}

	public function testClosure() {
		$this->bus->push(function () {
			AsyncBusTest::$lastCommand = 'closure';
		});
		$this->runJobs();
		$this->assertEquals('closure', self::$lastCommand);
	}

	public function testClosureSelf() {
		$this->bus->push(function () {
			self::$lastCommand = 'closure-self';
		});
		$this->runJobs();
		$this->assertEquals('closure-self', self::$lastCommand);
	}


	public function testClosureThis() {
		// clean class to prevent phpunit putting closure in $this
		$test = new ThisClosureTest();
		$test->test($this->bus);
		$this->runJobs();
		$this->assertEquals('closure-this', self::$lastCommand);
	}

	public function testClosureBind() {
		$state = 'bar';
		$this->bus->push(function () use ($state) {
			self::$lastCommand = 'closure-' . $state;
		});
		$this->runJobs();
		$this->assertEquals('closure-bar', self::$lastCommand);
	}

	public function testFileFileAccessCommand() {
		$this->bus->push(new FilesystemCommand());
		$this->assertEquals('', self::$lastCommand);
		$this->runJobs();
		$this->assertEquals('FileAccess', self::$lastCommand);
	}

	public function testFileFileAccessCommandSync() {
		$this->bus->requireSync('\OC\Command\FileAccess');
		$this->bus->push(new FilesystemCommand());
		$this->assertEquals('FileAccess', self::$lastCommand);
		self::$lastCommand = '';
		$this->runJobs();
		$this->assertEquals('', self::$lastCommand);
	}


	private function runJobs() {
		$jobs = $this->jobList->getAll();
		foreach ($jobs as $job) {
			$job->execute($this->jobList);
		}
	}
}
