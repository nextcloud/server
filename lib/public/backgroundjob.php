<?php
/**
* ownCloud
*
* @author Jakob Sack
* @copyright 2012 Jakob Sack owncloud@jakobsack.de
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

/**
 * Public interface of ownCloud forbackground jobs.
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides functions to manage backgroundjobs in ownCloud
 *
 * There are two kind of background jobs in ownCloud: regular tasks and
 * queued tasks.
 *
 * Regular tasks have to be registered in appinfo.php and
 * will run on a regular base. Fetching news could be a task that should run
 * frequently.
 *
 * Queued tasks have to be registered each time you want to execute them.
 * An example of the queued task would be the creation of the thumbnail. As
 * soon as the user uploads a picture the gallery app registers the queued
 * task "create thumbnail" and saves the path in the parameter instead of doing
 * the work right away. This makes the app more responsive. As soon as the task
 * is done it will be deleted from the list.
 */
class BackgroundJob {
	/**
	 * @brief get the execution type of background jobs
	 * @return string
	 *
	 * This method returns the type how background jobs are executed. If the user
	 * did not select something, the type is ajax.
	 */
	public static function getExecutionType() {
		return \OC_BackgroundJob::getExecutionType();
	}

	/**
	 * @brief sets the background jobs execution type
	 * @param string $type execution type
	 * @return boolean
	 *
	 * This method sets the execution type of the background jobs. Possible types
	 * are "none", "ajax", "webcron", "cron"
	 */
	public static function setExecutionType( $type ) {
		return \OC_BackgroundJob::setExecutionType( $type );
	}

	/**
	 * @param \OC\BackgroundJob\Job|string $job
	 * @param mixed $argument
	 */
	public static function registerJob($job, $argument = null){
		$jobList = new \OC\BackgroundJob\JobList();
		$jobList->add($job, $argument);
	}

}
