<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Command;

use OCP\Command\IBus;
use OCP\Command\ICommand;

class QueueBus implements IBus {
	/**
	 * @var (ICommand|callable)[]
	 */
	private $queue;

	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 */
	public function push($command) {
		$this->queue[] = $command;
	}

	/**
	 * Require all commands using a trait to be run synchronous
	 *
	 * @param string $trait
	 */
	public function requireSync($trait) {
	}

	/**
	 * @param \OCP\Command\ICommand | callable $command
	 */
	private function runCommand($command) {
		if ($command instanceof ICommand) {
			$command->handle();
		} else {
			$command();
		}
	}

	public function run() {
		while ($command = array_shift($this->queue)) {
			$this->runCommand($command);
		}
	}
}
