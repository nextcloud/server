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

$start = DateTime::createFromFormat('U', $_GET['start']);
$end = DateTime::createFromFormat('U', $_GET['end']);

$events = OC_Calendar_Object::allInPeriod($_GET['calendar_id'], $start, $end);
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone", "Europe/London");
$return = array();
foreach($events as $event)
{
	$return_event = array();
	$object = OC_Calendar_Object::parse($event['calendardata']);
	$vevent = $object->VEVENT;
	$dtstart = $vevent->DTSTART;
	$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
	$start_dt = $dtstart->getDateTime();
	$end_dt = $dtend->getDateTime();
	if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE)
	{
		$return_event['allDay'] = true;
		$return_event['start'] = $start_dt->format('Y-m-d');
		$end_dt->modify('-1 sec');
		$return_event['end'] = $end_dt->format('Y-m-d');
	}else{
		$start_dt->setTimezone(new DateTimeZone($user_timezone));
		$end_dt->setTimezone(new DateTimeZone($user_timezone));
		$return_event['start'] = $start_dt->format('Y-m-d H:i:s');
		$return_event['end'] = $end_dt->format('Y-m-d H:i:s');
		$return_event['allDay'] = false;
	}
	$return_event['id'] = $event['id'];
	$return_event['title'] = $event['summary'];
	$return_event['description'] = isset($vevent->DESCRIPTION)?$vevent->DESCRIPTION->value:'';
	$return[] = $return_event;
}
OC_JSON::encodedPrint($return);
