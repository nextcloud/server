<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

/**
 * Public interface of ownCloud for background jobs.
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;


/**
 * This class provides functions to register backgroundjobs in ownCloud
 *
 * To create a new backgroundjob create a new class that inherits from either \OC\BackgroundJob\Job,
 * \OC\BackgroundJob\QueuedJob or \OC\BackgroundJob\TimedJob and register it using
 * \OCP\BackgroundJob->registerJob($job, $argument), $argument will be passed to the run() function
 * of the job when the job is executed.
 *
 * A regular Job will be executed every time cron.php is run, a QueuedJob will only run once and a TimedJob
 * will only run at a specific interval which is to be specified in the constructor of the job by calling
 * $this->setInterval($interval) with $interval in seconds.
 * @since 4.5.0
 */
class BackgroundJob {
	/**
	 * get the execution type of background jobs
	 *
	 * @return string
	 *
	 * This method returns the type how background jobs are executed. If the user
	 * did not select something, the type is ajax.
	 * @since 5.0.0
	 */
	public static function getExecutionType() {
		return \OC::$server->getConfig()->getAppValue('core', 'backgroundjobs_mode', 'ajax');
	}

	/**
	 * sets the background jobs execution type
	 *
	 * @param string $type execution type
	 * @return false|null
	 *
	 * This method sets the execution type of the background jobs. Possible types
	 * are "none", "ajax", "webcron", "cron"
	 * @since 5.0.0
	 */
	public static function setExecutionType($type) {
		if( !in_array( $type, array('none', 'ajax', 'webcron', 'cron'))) {
			return false;
		}
		\OC::$server->getConfig()->setAppValue('core', 'backgroundjobs_mode', $type);
	}

	/**
	 * @param string $job
	 * @param mixed $argument
	 * @deprecated 8.1.0 Use \OC::$server->getJobList()->add() instead
	 * @since 6.0.0
	 */
	public static function registerJob($job, $argument = null) {
		$jobList = \OC::$server->getJobList();
		$jobList->add($job, $argument);
	}

	/**
	 * @deprecated 6.0.0
	 * creates a regular task
	 * @param string $klass class name
	 * @param string $method method name
	 * @return boolean|null
	 * @since 4.5.0
	 */
	public static function addRegularTask($klass, $method) {
		if (!\OCP\Util::needUpgrade()) {
			self::registerJob('OC\BackgroundJob\Legacy\RegularJob', array($klass, $method));
			return true;
		}
	}

	/**
	 * @deprecated 6.0.0
	 * gets all regular tasks
	 * @return array
	 *
	 * key is string "$klass-$method", value is array( $klass, $method )
	 * @since 4.5.0
	 */
	static public function allRegularTasks() {
		return [];
	}

	/**
	 * @deprecated 6.0.0
	 * Gets one queued task
	 * @param int $id ID of the task
	 * @return BackgroundJob\IJob|null
	 * @since 4.5.0
	 */
	public static function findQueuedTask($id) {
		$jobList = \OC::$server->getJobList();
		return $jobList->getById($id);
	}

	/**
	 * @deprecated 6.0.0
	 * Gets all queued tasks
	 * @return array an array of associative arrays
	 * @since 4.5.0
	 */
	public static function allQueuedTasks() {
		return [];
	}

	/**
	 * @deprecated 6.0.0
	 * Gets all queued tasks of a specific app
	 * @param string $app app name
	 * @return array an array of associative arrays
	 * @since 4.5.0
	 */
	public static function queuedTaskWhereAppIs($app) {
		return [];
	}

	/**
	 * @deprecated 6.0.0
	 * queues a task
	 * @param string $app app name
	 * @param string $class class name
	 * @param string $method method name
	 * @param string $parameters all useful data as text
	 * @return boolean id of task
	 * @since 4.5.0
	 */
	public static function addQueuedTask($app, $class, $method, $parameters) {
		self::registerJob('OC\BackgroundJob\Legacy\QueuedJob', array('app' => $app, 'klass' => $class, 'method' => $method, 'parameters' => $parameters));
		return true;
	}

	/**
	 * @deprecated 6.0.0
	 * deletes a queued task
	 * @param int $id id of task
	 * @return boolean|null
	 *
	 * Deletes a report
	 * @since 4.5.0
	 */
	public static function deleteQueuedTask($id) {
		$jobList = \OC::$server->getJobList();
		$job = $jobList->getById($id);
		if ($job) {
			$jobList->remove($job);
		}
	}
}
