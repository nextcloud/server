<?php

// Init owncloud
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('tasks');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), true);
$cid = reset($calendars)['id'];

$input = $_GET['text'];
$request = array();
$request['summary'] = $input;
$request["categories"] = null;
$request['priority'] = null;
$request['percent_complete'] = null;
$request['completed'] = null;
$request['location'] = null;
$request['due'] = null;
$request['description'] = null;
$vcalendar = OC_Task_App::createVCalendarFromRequest($request);
$id = OC_Calendar_Object::add($cid, $vcalendar->serialize());

$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task = OC_Task_App::arrayForJSON($id, $vcalendar->VTODO, $user_timezone);

OC_JSON::success(array('task' => $task));
