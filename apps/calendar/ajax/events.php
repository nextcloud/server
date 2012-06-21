<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
session_write_close();

// Look for the calendar id
$calendar_id = null;
if (strval(intval($_GET['calendar_id'])) == strval($_GET['calendar_id'])) { // integer for sure.
	$id = intval($_GET['calendar_id']);
	$calendarrow = OC_Calendar_App::getCalendar($id, true, false); // Let's at least security check otherwise we might as well use OC_Calendar_Calendar::find()
	if($calendarrow !== false && is_int($calendar_id['userid']) && $id == $calendar_id['userid']) {
		$calendar_id = $id;
	}
}
$calendar_id = (is_null($calendar_id)?strip_tags($_GET['calendar_id']):$calendar_id);

$start = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['start']):new DateTime('@' . $_GET['start']);
$end = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['end']):new DateTime('@' . $_GET['end']);
$events = OC_Calendar_App::getrequestedEvents($_GET['calendar_id'], $start, $end);
$output = array();
foreach($events as $event){
	$output = array_merge($output, OC_Calendar_App::generateEventOutput($event, $start, $end));
}
OCP\JSON::encodedPrint(OCP\Util::sanitizeHTML($output));
