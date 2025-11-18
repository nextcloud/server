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
use Test\Command\FilesystemCommand;
use Test\Command\SimpleCommand;
use Test\Command\StateFullCommand;

/**
 * Wrap a command in the background job interface
 */
class CommandJob extends QueuedJob {
	protected function run($argument) {
		$command = unserialize($argument, ['allowed_classes' => [
			SimpleCommand::class,
			StateFullCommand::class,
			FilesystemCommand::class,
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
