<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use OCP\BackgroundJob\QueuedJob;
use OCP\Command\ICommand;

/**
 * Wrap a command in the background job interface
 */
class CommandJob extends QueuedJob {
	protected function run($argument) {
		$command = unserialize($argument);
		if ($command instanceof ICommand) {
			$command->handle();
		} else {
			throw new \InvalidArgumentException('Invalid serialized command');
		}
	}
}
