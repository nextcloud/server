<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Command;

use OCP\BackgroundJob\IJobList;
use OCP\Command\ICommand;

class CronBus extends AsyncBus {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	protected function queueCommand(ICommand $command): void {
		$this->jobList->add(CommandJob::class, serialize($command));
	}
}
