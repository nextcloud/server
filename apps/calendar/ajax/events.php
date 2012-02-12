<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once ('../../../lib/base.php');
require_once('../../../3rdparty/when/When.php');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$start = DateTime::createFromFormat('U', $_GET['start']);
$end = DateTime::createFromFormat('U', $_GET['end']);
if($_GET['calendar_id'] == 'shared'){
	$calendars = OC_Calendar_Share::allSharedwithuser(OC_USER::getUser(), OC_Calendar_Share::CALENDAR, 1);
	$events = array();
	foreach($calendars as $calendar){
		$calendarevents = OC_Calendar_Object::allInPeriod($calendar['calendarid'], $start, $end);
		$events = array_merge($events, $calendarevents);
	}
}else{
	$calendar = OC_Calendar_Calendar::find($_GET['calendar_id']);
	if($calendar['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	$events = OC_Calendar_Object::allInPeriod($_GET['calendar_id'], $start, $end);
}
$user_timezone = OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone', date_default_timezone_get());

$return = array();

foreach($events as $event){
	$object = OC_VObject::parse($event['calendardata']);
	$vevent = $object->VEVENT;
	$dtstart = $vevent->DTSTART;
	$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
	$return_event = array();
	$start_dt = $dtstart->getDateTime();
	$end_dt = $dtend->getDateTime();
	if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE){
		$return_event['allDay'] = true;
	}else{
		$return_event['allDay'] = false;
		$start_dt->setTimezone(new DateTimeZone($user_timezone));
		$end_dt->setTimezone(new DateTimeZone($user_timezone));
	}
	if($event['repeating'] == 1){
		$duration = (double) $end_dt->format('U') - (double) $start_dt->format('U');
		$r = new When();
		$r->recur((string) $start_dt->format('Ymd\THis'))->rrule((string) $vevent->RRULE);
		while($result = $r->next()){
			if($result->format('U') > $_GET['end']){
				break;
			}
			if($return_event['allDay'] == true){
				$return_event['start'] = $result->format('Y-m-d');
				$return_event['end'] = date('Y-m-d', $result->format('U') + --$duration);
			}else{
				$return_event['start'] = $result->format('Y-m-d H:i:s');
				$return_event['end'] = date('Y-m-d H:i:s', $result->format('U') + $duration);
			}
			$return[] = OC_Calendar_App::prepareForOutput($event, $vevent, $return_event);
		}
	}else{
		$return_event = array();
		if ($dtstart->getDateType() == Sabre_VObject_Element_DateTime::DATE){
			$return_event['allDay'] = true;
			$return_event['start'] = $start_dt->format('Y-m-d');
			$end_dt->modify('-1 sec');
			$return_event['end'] = $end_dt->format('Y-m-d');
		}else{
			$return_event['start'] = $start_dt->format('Y-m-d H:i:s');
			$return_event['end'] = $end_dt->format('Y-m-d H:i:s');
			$return_event['allDay'] = false;
		}
		$return[] = OC_Calendar_App::prepareForOutput($event, $vevent, $return_event);
	}
}
OC_JSON::encodedPrint($return);
?>