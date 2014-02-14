<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\BackgroundJob;

use OCP\BackgroundJob\IJob;

abstract class Job implements IJob {
	/**
	 * @var int $id
	 */
	protected $id;

	/**
	 * @var int $lastRun
	 */
	protected $lastRun;

	/**
	 * @var mixed $argument
	 */
	protected $argument;

	/**
	 * @param JobList $jobList
	 * @param \OC\Log $logger
	 */
	public function execute($jobList, $logger = null) {
		$jobList->setLastRun($this);
		try {
			$this->run($this->argument);
		} catch (\Exception $e) {
			if ($logger) {
				$logger->error('Error while running background job: ' . $e->getMessage());
			}
			$jobList->remove($this, $this->argument);
		}
	}

	abstract protected function run($argument);

	public function setId($id) {
		$this->id = $id;
	}

	public function setLastRun($lastRun) {
		$this->lastRun = $lastRun;
	}

	public function setArgument($argument) {
		$this->argument = $argument;
	}

	public function getId() {
		return $this->id;
	}

	public function getLastRun() {
		return $this->lastRun;
	}

	public function getArgument() {
		return $this->argument;
	}
}
