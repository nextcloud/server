<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
OCP\JSON::callCheck();

if(trim($_POST['name']) == ''){
	OCP\JSON::error(array('message'=>'empty'));
	exit;
}
$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
foreach($calendars as $cal){
	if($cal['displayname'] == $_POST['name']){
		OCP\JSON::error(array('message'=>'namenotavailable'));
		exit;
	}
}

$userid = OCP\USER::getUser();
$calendarid = OC_Calendar_Calendar::addCalendar($userid, strip_tags($_POST['name']), 'VEVENT,VTODO,VJOURNAL', null, 0, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, 1);

$calendar = OC_Calendar_Calendar::find($calendarid);
$tmpl = new OCP\Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
OCP\JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'eventSource' => OC_Calendar_Calendar::getEventSourceInfo($calendar),
));