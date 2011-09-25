<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
$events = array();
foreach($calendars as $calendar) {
	$tmp = OC_Calendar_Object::all($calendar['id']);
	$events = array_merge($events, $tmp);
}
$select_year = $_GET["year"];
$return_events = array();
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
foreach($events as $event)
{
	if ($select_year != substr($event['startdate'], 0, 4))
		continue;
	$start_dt = new DateTime($event['startdate'], new DateTimeZone('UTC'));
	$start_dt->setTimezone(new DateTimeZone($user_timezone));
	$end_dt = new DateTime($event['enddate'], new DateTimeZone('UTC'));
	$end_dt->setTimezone(new DateTimeZone($user_timezone));
	$year  = $start_dt->format('Y');
	$month = $start_dt->format('n') - 1; // return is 0 based
	$day   = $start_dt->format('j');
	$hour  = $start_dt->format('G');

	// hack
	if (strstr($event['calendardata'], 'DTSTART;VALUE=DATE:')) {
		$hour = 'allday';
	}
	$return_event = array();
	foreach(array('id', 'calendarid', 'objecttype', 'repeating') as $prop)
	{
		$return_event[$prop] = $event[$prop];
	}
	$return_event['startdate'] = explode('|', $start_dt->format('Y|m|d|H|i'));
	$return_event['enddate'] = explode('|', $end_dt->format('Y|m|d|H|i'));
	$return_event['description'] = $event['summary'];
	if ($hour == 'allday')
	{
		$return_event['allday'] = true;
	}
	if (isset($return_events[$year][$month][$day][$hour]))
	{
		$return_events[$year][$month][$day][$hour][] = $return_event;
	}
	else
	{
		$return_events[$year][$month][$day][$hour] = array(1 => $return_event);
	}
}
OC_JSON::encodedPrint($return_events);
?>
