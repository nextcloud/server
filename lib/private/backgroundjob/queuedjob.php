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
 * create a background job that is to be executed once
 *
 * @package OC\BackgroundJob
 */
abstract class QueuedJob extends Job {
	/**
	 * run the job, then remove it from the joblist
	 *
	 * @param JobList $jobList
	 * @param \OC\Log $logger
	 */
	public function execute($jobList, $logger = null) {
		$jobList->remove($this);
		parent::execute($jobList, $logger);
	}
}
