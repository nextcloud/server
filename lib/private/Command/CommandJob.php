<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use OCA\Files_Trashbin\Command\Expire;
use OCP\BackgroundJob\QueuedJob;
use OCP\Command\ICommand;

/**
 * Wrap a command in the background job interface
 */
class CommandJob extends QueuedJob {
	protected function run($argument) {
		$command = unserialize($argument, ['allowed_classes' => [
			\Test\Command\SimpleCommand::class,
			\Test\Command\StateFullCommand::class,
			\Test\Command\FilesystemCommand::class,
			Expire::class,
			\OCA\Files_Versions\Command\Expire::class,
		]]);
		if ($command instanceof ICommand) {
			$command->handle();
		} else {
			throw new \InvalidArgumentException('Invalid serialized command');
		}
	}
}
