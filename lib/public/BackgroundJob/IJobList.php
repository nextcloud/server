<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

namespace OCP\BackgroundJob;

/**
 * Interface IJobList
 *
 * @package OCP\BackgroundJob
 * @since 7.0.0
 */
interface IJobList {
	/**
	 * Add a job to the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument The argument to be passed to $job->run() when the job is exectured
	 * @since 7.0.0
	 */
	public function add($job, $argument = null);

	/**
	 * Remove a job from the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument
	 * @since 7.0.0
	 */
	public function remove($job, $argument = null);

	/**
	 * check if a job is in the list
	 *
	 * @param \OCP\BackgroundJob\IJob|string $job
	 * @param mixed $argument
	 * @return bool
	 * @since 7.0.0
	 */
	public function has($job, $argument);

	/**
	 * get all jobs in the list
	 *
	 * @return \OCP\BackgroundJob\IJob[]
	 * @since 7.0.0
	 * @deprecated 9.0.0 - This method is dangerous since it can cause load and
	 * memory problems when creating too many instances.
	 */
	public function getAll();

	/**
	 * get the next job in the list
	 *
	 * @return \OCP\BackgroundJob\IJob|null
	 * @since 7.0.0
	 */
	public function getNext();

	/**
	 * @param int $id
	 * @return \OCP\BackgroundJob\IJob|null
	 * @since 7.0.0
	 */
	public function getById($id);

	/**
	 * set the job that was last ran to the current time
	 *
	 * @param \OCP\BackgroundJob\IJob $job
	 * @since 7.0.0
	 */
	public function setLastJob($job);

	/**
	 * Remove the reservation for a job
	 *
	 * @param IJob $job
	 * @since 9.1.0
	 */
	public function unlockJob($job);

	/**
	 * get the id of the last ran job
	 *
	 * @return int
	 * @since 7.0.0
	 * @deprecated 9.1.0 - The functionality behind the value is deprecated, it
	 *    only tells you which job finished last, but since we now allow multiple
	 *    executors to run in parallel, it's not used to calculate the next job.
	 */
	public function getLastJob();

	/**
	 * set the lastRun of $job to now
	 *
	 * @param \OCP\BackgroundJob\IJob $job
	 * @since 7.0.0
	 */
	public function setLastRun($job);
}
