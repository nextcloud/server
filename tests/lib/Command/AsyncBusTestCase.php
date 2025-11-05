<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Command;

use OC\Command\FileAccess;
use OCP\Command\IBus;
use OCP\Command\ICommand;
use Test\TestCase;

class SimpleCommand implements ICommand {
	public function handle() {
		AsyncBusTestCase::$lastCommand = 'SimpleCommand';
	}
}

class StateFullCommand implements ICommand {
	public function __construct(
		private $state,
	) {
	}

	public function handle() {
		AsyncBusTestCase::$lastCommand = $this->state;
	}
}

class FilesystemCommand implements ICommand {
	use FileAccess;

	public function handle() {
		AsyncBusTestCase::$lastCommand = 'FileAccess';
	}
}

abstract class AsyncBusTestCase extends TestCase {
	/**
	 * Basic way to check output from a command
	 *
	 * @var string
	 */
	public static $lastCommand;

	/**
	 * @var IBus
	 */
	private $bus;

	public static function DummyCommand() {
		self::$lastCommand = 'static';
	}

	/**
	 * @return IBus
	 */
	protected function getBus() {
		if (!$this->bus instanceof IBus) {
			$this->bus = $this->createBus();
		}
		return $this->bus;
	}

	/**
	 * @return IBus
	 */
	abstract protected function createBus();

	protected function setUp(): void {
		self::$lastCommand = '';
	}

	public function testSimpleCommand(): void {
		$command = new SimpleCommand();
		$this->getBus()->push($command);
		$this->runJobs();
		$this->assertEquals('SimpleCommand', self::$lastCommand);
	}

	public function testStateFullCommand(): void {
		$command = new StateFullCommand('foo');
		$this->getBus()->push($command);
		$this->runJobs();
		$this->assertEquals('foo', self::$lastCommand);
	}

	public function testFileFileAccessCommand(): void {
		$this->getBus()->push(new FilesystemCommand());
		$this->assertEquals('', self::$lastCommand);
		$this->runJobs();
		$this->assertEquals('FileAccess', self::$lastCommand);
	}

	public function testFileFileAccessCommandSync(): void {
		$this->getBus()->requireSync('\OC\Command\FileAccess');
		$this->getBus()->push(new FilesystemCommand());
		$this->assertEquals('FileAccess', self::$lastCommand);
		self::$lastCommand = '';
		$this->runJobs();
		$this->assertEquals('', self::$lastCommand);
	}


	abstract protected function runJobs();
}
