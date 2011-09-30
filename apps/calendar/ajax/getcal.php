<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('calendar');

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
$events = array();
$return = array('calendars'=>array());
foreach($calendars as $calendar) {
	$tmp = OC_Calendar_Object::all($calendar['id']);
	$events = array_merge($events, $tmp);
	$return['calendars'][$calendar['id']] = array(
		'displayname' => $calendar['displayname'],
		'color'       => $calendar['calendarcolor']
	);
}

$select_year = $_GET["year"];
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
foreach($events as $event)
{
	if ($select_year != substr($event['startdate'], 0, 4))
		continue;
	$object = Sabre_VObject_Reader::read($event['calendardata']);
	$vevent = $object->VEVENT;
	$dtstart = $vevent->DTSTART;
	$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
	$start_dt = $dtstart->getDateTime();
	$start_dt->setTimezone(new DateTimeZone($user_timezone));
	$end_dt = $dtend->getDateTime();
	$end_dt->setTimezone(new DateTimeZone($user_timezone));
	$year  = $start_dt->format('Y');
	$month = $start_dt->format('n') - 1; // return is 0 based
	$day   = $start_dt->format('j');
	$hour  = $start_dt->format('G');
	if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE) {
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
	if (isset($return[$year][$month][$day][$hour]))
	{
		$return[$year][$month][$day][$hour][] = $return_event;
	}
	else
	{
		$return[$year][$month][$day][$hour] = array(1 => $return_event);
	}
}
OC_JSON::encodedPrint($return);
