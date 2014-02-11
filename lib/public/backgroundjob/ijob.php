<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\BackgroundJob;

interface IJob {
	/**
	 * @param \OCP\BackgroundJob\IJobList $jobList
	 * @param \OC\Log $logger
	 */
	public function execute($jobList, $logger = null);

	public function setId($id);

	public function setLastRun($lastRun);

	public function setArgument($argument);

	public function getId();

	public function getLastRun();

	public function getArgument();
}
