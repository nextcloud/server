<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use OCP\Command\IBus;
use OCP\Command\ICommand;

class QueueBus implements IBus {
	/**
	 * @var ICommand[]|callable[]
	 */
	private $queue = [];

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
			// ensure the command can be serialized
			$serialized = serialize($command);
			if (strlen($serialized) > 4000) {
				throw new \InvalidArgumentException('Trying to push a command which serialized form can not be stored in the database (>4000 character)');
			}
			$unserialized = unserialize($serialized);
			$unserialized->handle();
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
