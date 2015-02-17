<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Command;

use OCP\Command\IBus;
use OCP\Command\ICommand;
use SuperClosure\Serializer;

/**
 * Asynchronous command bus that uses the background job system as backend
 */
class AsyncBus implements IBus {
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
		$this->jobList->add($this->getJobClass($command), $this->serializeCommand($command));
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
