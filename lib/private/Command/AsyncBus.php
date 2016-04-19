<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Command;

use OCP\Command\IBus;
use OCP\Command\ICommand;
use SuperClosure\Serializer;

/**
 * Asynchronous command bus that uses the background job system as backend
 */
class AsyncBus implements IBus {
	use AsyncBusTrait;
	/**
	 * @var \OCP\BackgroundJob\IJobList
	 */
	private $jobList;

	/**
	 * @param \OCP\BackgroundJob\IJobList $jobList
	 */
	function __construct($jobList) {
		$this->jobList = $jobList;
	}

	/**
	 * Schedule a command to be fired
	 *
	 * @param \OCP\Command\ICommand | callable $command
	 */
	public function push($command) {
		if ($this->canRunAsync($command)) {
			$this->jobList->add($this->getJobClass($command), $this->serializeCommand($command));
		} else {
			$this->runCommand($command);
		}
	}

	/**
	 * @param \OCP\Command\ICommand | callable $command
	 * @return string
	 */
	private function getJobClass($command) {
		if ($command instanceof \Closure) {
			return 'OC\Command\ClosureJob';
		} else if (is_callable($command)) {
			return 'OC\Command\CallableJob';
		} else if ($command instanceof ICommand) {
			return 'OC\Command\CommandJob';
		} else {
			throw new \InvalidArgumentException('Invalid command');
		}
	}

	/**
	 * @param \OCP\Command\ICommand | callable $command
	 * @return string
	 */
	private function serializeCommand($command) {
		if ($command instanceof \Closure) {
			$serializer = new Serializer();
			return $serializer->serialize($command);
		} else if (is_callable($command) or $command instanceof ICommand) {
			return serialize($command);
		} else {
			throw new \InvalidArgumentException('Invalid command');
		}
	}
}
