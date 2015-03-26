<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCP\BackgroundJob;

interface IJobList {
	/**
	 * Add a job to the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument The argument to be passed to $job->run() when the job is exectured
	 * @param string $job
	 * @return void
	 */
	public function add($job, $argument = null);

	/**
	 * Remove a job from the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument
	 * @return void
	 */
	public function remove($job, $argument = null);

	/**
	 * check if a job is in the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument
	 * @return bool
	 */
	public function has($job, $argument);

	/**
	 * get all jobs in the list
	 *
	 * @return \OCP\BackgroundJob\IJob[]
	 */
	public function getAll();

	/**
	 * get the next job in the list
	 *
	 * @return \OCP\BackgroundJob\IJob
	 */
	public function getNext();

	/**
	 * @param int $id
	 * @return \OCP\BackgroundJob\IJob
	 */
	public function getById($id);

	/**
	 * set the job that was last ran to the current time
	 *
	 * @param \OCP\BackgroundJob\IJob $job
	 * @return void
	 */
	public function setLastJob($job);

	/**
	 * get the id of the last ran job
	 *
	 * @return int
	 */
	public function getLastJob();

	/**
	 * set the lastRun of $job to now
	 *
	 * @param \OCP\BackgroundJob\IJob $job
	 * @return void
	 */
	public function setLastRun($job);
}
