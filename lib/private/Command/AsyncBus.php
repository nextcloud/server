<?php

declare(strict_types=1);

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
	private array $syncTraits = [];

	/**
	 * Schedule a command to be fired
	 */
	public function push(ICommand $command): void {
		if ($this->canRunAsync($command)) {
			$this->queueCommand($command);
		} else {
			$this->runCommand($command);
		}
	}

	/**
	 * Queue a command in the bus
	 */
	abstract protected function queueCommand(ICommand $command);

	/**
	 * Require all commands using a trait to be run synchronous
	 *
	 * @param string $trait
	 */
	public function requireSync(string $trait): void {
		$this->syncTraits[] = trim($trait, '\\');
	}

	private function runCommand(ICommand $command): void {
		$command->handle();
	}

	/**
	 * @return bool
	 */
	private function canRunAsync(ICommand $command): bool {
		$traits = $this->getTraits($command);
		foreach ($traits as $trait) {
			if (in_array($trait, $this->syncTraits, true)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @return string[]
	 */
	private function getTraits(ICommand $command): array {
		return class_uses($command);
	}
}
