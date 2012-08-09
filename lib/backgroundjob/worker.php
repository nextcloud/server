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
 * This class does the dirty work.
 * 
 * TODO: locking in doAllSteps
 */
class OC_BackgroundJob_Worker{
	/**
	 * @brief executes all tasks
	 * @return boolean
	 *
	 * This method executes all regular tasks and then all scheduled tasks.
	 * This method should be called by cli scripts that do not let the user
	 * wait.
	 */
	public static function doAllSteps(){
		// Do our regular work
		$regular_tasks = OC_BackgroundJob_RegularTask::all();
		foreach( $regular_tasks as $key => $value ){
			call_user_func( $value );
		}

		// Do our scheduled tasks
		$scheduled_tasks = OC_BackgroundJob_ScheduledTask::all();
		foreach( $scheduled_tasks as $task ){
			call_user_func( array( $task['klass'], $task['method'] ), $task['parameters'] );
			OC_BackgroundJob_ScheduledTask::delete( $task['id'] );
		}
		
		return true;
	}

	/**
	 * @brief does a single task
	 * @return boolean
	 *
	 * This method executes one task. It saves the last state and continues
	 * with the next step. This method should be used by webcron and ajax
	 * services.
	 */
	public static function doNextStep(){
		$laststep = OC_Appconfig::getValue( 'core', 'backgroundjobs_step', 'regular_tasks' );
		
		if( $laststep == 'regular_tasks' ){
			// get last app
			$lasttask = OC_Appconfig::getValue( 'core', 'backgroundjobs_task', '' );

			// What's the next step?
			$regular_tasks = OC_BackgroundJob_RegularTask::all();
			ksort( $regular_tasks );
			$done = false;
			
			// search for next background job
			foreach( $regular_tasks as $key => $value ){
				if( strcmp( $lasttask, $key ) > 0 ){
					OC_Appconfig::getValue( 'core', 'backgroundjobs_task', $key );
					$done = true;
					call_user_func( $value );
					break;
				}
			}

			if( $done == false ){
				// Next time load scheduled tasks
				OC_Appconfig::setValue( 'core', 'backgroundjobs_step', 'scheduled_tasks' );
			}
		}
		else{
			$tasks = OC_BackgroundJob_ScheduledTask::all();
			if( count( $tasks )){
				$task = $tasks[0];
				// delete job before we execute it. This prevents endless loops
				// of failing jobs.
				OC_BackgroundJob_ScheduledTask::delete($task['id']);

				// execute job
				call_user_func( array( $task['klass'], $task['method'] ), $task['parameters'] );
			}
			else{
				// Next time load scheduled tasks
				OC_Appconfig::setValue( 'core', 'backgroundjobs_step', 'regular_tasks' );
				OC_Appconfig::setValue( 'core', 'backgroundjobs_task', '' );
			}
		}
		
		return true;
	}
}
