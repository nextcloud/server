<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
