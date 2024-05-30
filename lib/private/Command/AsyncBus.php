<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use OCP\Command\IBus;
use OCP\Command\ICommand;

/**
 * Asynchronous command bus that uses the background job system as backend
 */
abstract class AsyncBus implements IBus {
	/**
	 * List of traits for command which require sync execution
	 *
	 * @var string[]
	 */
	private $syncTraits = [];

	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 */
	public function push($command) {
		if ($this->canRunAsync($command)) {
			$this->queueCommand($command);
		} else {
			$this->runCommand($command);
		}
	}

	/**
	 * Queue a command in the bus
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 */
	abstract protected function queueCommand($command);

	/**
	 * Require all commands using a trait to be run synchronous
	 *
	 * @param string $trait
	 */
	public function requireSync($trait) {
		$this->syncTraits[] = trim($trait, '\\');
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

	/**
	 * @param \OCP\Command\ICommand | callable $command
	 * @return bool
	 */
	private function canRunAsync($command) {
		$traits = $this->getTraits($command);
		foreach ($traits as $trait) {
			if (in_array($trait, $this->syncTraits)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param \OCP\Command\ICommand | callable $command
	 * @return string[]
	 */
	private function getTraits($command) {
		if ($command instanceof ICommand) {
			return class_uses($command);
		} else {
			return [];
		}
	}
}
