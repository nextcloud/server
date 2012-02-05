<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

if(trim($_POST['name']) == ''){
	OC_JSON::error(array('message'=>'empty'));
	exit;
}
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
foreach($calendars as $cal){
	if($cal['displayname'] == $_POST['name'] && $cal['id'] != $_POST['id']){
		OC_JSON::error(array('message'=>'namenotavailable'));
		exit;
	}
}

$calendarid = $_POST['id'];
$calendar = OC_Calendar_App::getCalendar($calendarid);//access check
OC_Calendar_Calendar::editCalendar($calendarid, strip_tags($_POST['name']), null, null, null, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, $_POST['active']);

$calendar = OC_Calendar_App::getCalendar($calendarid);
$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
OC_JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'eventSource' => OC_Calendar_Calendar::getEventSourceInfo($calendar),
));
