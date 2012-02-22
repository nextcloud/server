<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());

$tasks = array();
foreach( $calendars as $calendar ){
        $calendar_tasks = OC_Calendar_Object::all($calendar['id']);
        foreach( $calendar_tasks as $task ){
                if($task['objecttype']!='VTODO'){
                        continue;
                }
                if(is_null($task['summary'])){
                        continue;
                }
		$object = OC_VObject::parse($task['calendardata']);
		$vtodo = $object->VTODO;
                $task = array( 'id' => $task['id'] );
		$task['summary'] = $vtodo->getAsString('SUMMARY');
		$task['description'] = $vtodo->getAsString('DESCRIPTION');
		$task['location'] = $vtodo->getAsString('LOCATION');
		$task['categories'] = $vtodo->getAsArray('CATEGORIES');
		$due = $vtodo->DUE;
		if ($due) {
			$due = $due->getDateTime();
			$due->setTimezone(new DateTimeZone($user_timezone));
			$task['due'] = $due->format('Y-m-d H:i:s');
		}
		else {
			$task['due'] = false;
		}
		$task['priority'] = $vtodo->getAsString('PRIORITY');
		$completed = $vtodo->COMPLETED;
		if ($completed) {
			$completed = $completed->getDateTime();
			$completed->setTimezone(new DateTimeZone($user_timezone));
			$task['completed'] = $completed->format('Y-m-d H:i:s');
		}
		else {
			$task['completed'] = false;
		}
		$task['complete'] = $vtodo->getAsString('PERCENT-COMPLETE');
		$tasks[] = $task;
        }
}

OC_JSON::encodedPrint($tasks);
