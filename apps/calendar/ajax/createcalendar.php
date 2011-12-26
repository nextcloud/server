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

$userid = OC_User::getUser();
$calendarid = OC_Calendar_Calendar::addCalendar($userid, $_POST['name'], 'VEVENT,VTODO,VJOURNAL', null, 0, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, 1);

$calendar = OC_Calendar_Calendar::find($calendarid);
$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
OC_JSON::success(array(
	'page' => $tmpl->fetchPage(),
	'eventSource' => OC_Calendar_Calendar::getEventSourceInfo($calendar),
));
