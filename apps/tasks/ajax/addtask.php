<?php

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tasks');
OCP\JSON::callCheck();

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
$first_calendar = reset($calendars);
$cid = $first_calendar['id'];

$input = $_POST['text'];
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

$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
$task = OC_Task_App::arrayForJSON($id, $vcalendar->VTODO, $user_timezone);

OCP\JSON::success(array('task' => $task));
