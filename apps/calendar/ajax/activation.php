<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../../lib/base.php");
if(!OC_USER::isLoggedIn()) {
	die("<script type=\"text/javascript\">document.location = oc_webroot;</script>");
}
OC_JSON::checkAppEnabled('calendar');
$calendarid = $_POST['calendarid'];
OC_Calendar_Calendar::setCalendarActive($calendarid, $_POST['active']);
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
OC_JSON::success(array(
	'active' => $calendar['active'],
	'eventSource' => OC_Calendar_Calendar::getEventSourceInfo($calendar),
));
