<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\BackgroundJob;

interface IJob {
	/**
	 * Run the background job with the registered argument
	 *
	 * @param \OCP\BackgroundJob\IJobList $jobList The job list that manages the state of this job
	 * @param \OC\Log $logger
	 * @return void
	 */
	public function execute($jobList, $logger = null);

	/**
	 * Get the id of the background job
	 * This id is determined by the job list when a job is added to the list
	 *
	 * @return int
	 */
	public function getId();

	/**
	 * Get the last time this job was run as unix timestamp
	 *
	 * @return int
	 */
	public function getLastRun();

	/**
	 * Get the argument associated with the background job
	 * This is the argument that will be passed to the background job
	 *
	 * @return mixed
	 */
	public function getArgument();
}
