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
		$tasks[] = OC_Task_App::arrayForJSON($task['id'], $vtodo, $user_timezone);
        }
}

OC_JSON::encodedPrint($tasks);
