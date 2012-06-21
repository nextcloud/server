<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
require_once('when/When.php');

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
session_write_close();

// Look for the calendar id
$calendar_id = OC_Calendar_App::getCalendar($_GET['calendar_id'], false, false);
if($calendar_id !== false){
	if(! is_numeric($calendar_id['userid']) && $calendar_id['userid'] != OCP\User::getUser()){
		OCP\JSON::error();
		exit;
	}
}
else {
	$calendar_id = $_GET['calendar_id'];
}

$start = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['start']):new DateTime('@' . $_GET['start']);
$end = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['end']):new DateTime('@' . $_GET['end']);
$events = OC_Calendar_App::getrequestedEvents($_GET['calendar_id'], $start, $end);
$output = array();
foreach($events as $event){
	$output = array_merge($output, OC_Calendar_App::generateEventOutput($event, $start, $end));
}
OCP\JSON::encodedPrint(OCP\Util::sanitizeHTML($output));
