<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\BackgroundJob;

/**
 * Class QueuedJob
 *
 * create a background job that is to be executed at an interval
 *
 * @package OC\BackgroundJob
 */
abstract class TimedJob extends Job {
	protected $interval = 0;

	/**
	 * set the interval for the job
	 *
	 * @param int $interval
	 */
	public function setInterval($interval) {
		$this->interval = $interval;
	}

	/**
	 * run the job if
	 *
	 * @param JobList $jobList
	 * @param \OC\Log $logger
	 */
	public function execute($jobList, $logger = null) {
		if ((time() - $this->lastRun) > $this->interval) {
			parent::execute($jobList, $logger);
		}
	}
}
