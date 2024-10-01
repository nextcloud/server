<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Command;

use Laravel\SerializableClosure\SerializableClosure;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\Command\ICommand;

class CronBus extends AsyncBus {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	protected function queueCommand($command): void {
		$this->jobList->add($this->getJobClass($command), $this->serializeCommand($command));
	}

	/**
	 * @param ICommand|callable $command
	 * @return class-string<IJob>
	 */
	private function getJobClass($command): string {
		if ($command instanceof \Closure) {
			return ClosureJob::class;
		} elseif (is_callable($command)) {
			return CallableJob::class;
		} elseif ($command instanceof ICommand) {
			return CommandJob::class;
		} else {
			throw new \InvalidArgumentException('Invalid command');
		}
	}

	/**
	 * @param ICommand|callable $command
	 * @return string
	 */
	private function serializeCommand($command): string {
		if ($command instanceof \Closure) {
			return serialize(new SerializableClosure($command));
		} elseif (is_callable($command) or $command instanceof ICommand) {
			return serialize($command);
		} else {
			throw new \InvalidArgumentException('Invalid command');
		}
	}
}
