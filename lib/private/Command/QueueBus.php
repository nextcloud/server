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

class QueueBus implements IBus {
	/**
	 * @var ICommand[]
	 */
	private array $queue = [];

	/**
	 * Schedule a command to be fired
	 */
	public function push(ICommand $command): void {
		$this->queue[] = $command;
	}

	/**
	 * Require all commands using a trait to be run synchronous
	 */
	public function requireSync(string $trait): void {
	}

	private function runCommand(ICommand $command): void {
		// ensure the command can be serialized
		$serialized = serialize($command);
		if (strlen($serialized) > 4000) {
			throw new \InvalidArgumentException('Trying to push a command which serialized form can not be stored in the database (>4000 character)');
		}
		$unserialized = unserialize($serialized, ['allowed_classes' => [
			\Test\Command\SimpleCommand::class,
			\Test\Command\StateFullCommand::class,
			\Test\Command\FilesystemCommand::class,
			\OCA\Files_Trashbin\Command\Expire::class,
			\OCA\Files_Versions\Command\Expire::class,
		]]);
		$unserialized->handle();
	}

	public function run(): void {
		while ($command = array_shift($this->queue)) {
			$this->runCommand($command);
		}
	}
}
