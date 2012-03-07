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
	 * @brief Deletes all Addressbooks of a certain user
	 * @param paramters parameters from postDeleteUser-Hook
	 * @return array
	 */
	public static function deleteUser($parameters) {
		$calendars = OC_Calendar_Calendar::allCalendars($parameters['uid']);

		foreach($calendars as $calendar) {
			OC_Calendar_Calendar::deleteCalendar($calendar['id']);
		}

		return true;
	}

	/**
	 * @brief Adds the CardDAV resource to the DAV server
	 * @param paramters parameters from initialize-Hook
	 * @return array
	 */
	public static function initializeCalDAV($parameters){
		// We need a backend, the root node and the caldav plugin
		$parameters['backends']['caldav'] = new OC_Connector_Sabre_CalDAV();
		$parameters['nodes'][] = new Sabre_CalDAV_CalendarRootNode($parameters['backends']['principal'], $parameters['backends']['caldav']);
		$parameters['plugins'][] = new Sabre_CalDAV_Plugin();
		return true;
	}
}
