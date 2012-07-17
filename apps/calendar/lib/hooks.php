<?php
/**
 * Copyright (c) 2011 Jakob Sack <mail@jakobsack.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class contains all hooks.
 */
class OC_Calendar_Hooks{
	/**
	 * @brief Creates default calendar for a user
	 * @param paramters parameters from postCreateUser-Hook
	 * @return array
	 */
	public static function createUser($parameters) {
		OC_Calendar_Calendar::addCalendar($parameters['uid'],'Default calendar');

		return true;
	}

	/**
	 * @brief Deletes all calendars of a certain user
	 * @param paramters parameters from postDeleteUser-Hook
	 * @return array
	 */
	public static function deleteUser($parameters) {
		$calendars = OC_Calendar_Calendar::allCalendars($parameters['uid']);

		foreach($calendars as $calendar) {
			OC_Calendar_Calendar::deleteCalendar($calendar['id']);
		}

		OC_Calendar_Share::post_userdelete($parameters['uid']);

		return true;
	}
}
