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
 * scheduled tasks.
 *
 * Regular tasks have to be registered in appinfo.php and
 * will run on a regular base. Fetching news could be a task that should run
 * frequently.
 *
 * Scheduled tasks have to be registered each time you want to execute them.
 * An example of the scheduled task would be the creation of the thumbnail. As
 * soon as the user uploads a picture the gallery app registers the scheduled
 * task "create thumbnail" and saves the path in the parameter. As soon as the
 * task is done it will be deleted from the list.
 */
class Backgroundjob {
	/**
	 * @brief creates a regular task
	 * @param $klass class name
	 * @param $method method name
	 * @return true
	 */
	public static function addRegularTask( $klass, $method ){
		return \OC_BackgroundJob_RegularTask::register( $klass, $method );
	}

	/**
	 * @brief gets all regular tasks
	 * @return associative array
	 *
	 * key is string "$klass-$method", value is array( $klass, $method )
	 */
	static public function allRegularTasks(){
		return \OC_BackgroundJob_RegularTask::all();
	}

	/**
	 * @brief Gets one scheduled task
	 * @param $id ID of the task
	 * @return associative array
	 */
	public static function findScheduledTask( $id ){
		return \OC_BackgroundJob_ScheduledTask::find( $id );
	}

	/**
	 * @brief Gets all scheduled tasks
	 * @return array with associative arrays
	 */
	public static function allScheduledTasks(){
		return \OC_BackgroundJob_ScheduledTask::all();
	}

	/**
	 * @brief Gets all scheduled tasks of a specific app
	 * @param $app app name
	 * @return array with associative arrays
	 */
	public static function scheduledTaskWhereAppIs( $app ){
		return \OC_BackgroundJob_ScheduledTask::whereAppIs( $app );
	}

	/**
	 * @brief schedules a task
	 * @param $app app name
	 * @param $klass class name
	 * @param $method method name
	 * @param $parameters all useful data as text
	 * @return id of task
	 */
	public static function addScheduledTask( $task, $klass, $method, $parameters ){
		return \OC_BackgroundJob_ScheduledTask::add( $task, $klass, $method, $parameters );
	}

	/**
	 * @brief deletes a scheduled task
	 * @param $id id of task
	 * @return true/false
	 *
	 * Deletes a report
	 */
	public static function deleteScheduledTask( $id ){
		return \OC_BackgroundJob_ScheduledTask::delete( $id );
	}
}
