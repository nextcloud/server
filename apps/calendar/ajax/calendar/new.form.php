<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
$calendarcolor_options = OC_Calendar_Calendar::getCalendarColorOptions();
$calendar = array(
	'id' => 'new',
	'displayname' => '',
	'calendarcolor' => '',
);
$tmpl = new OCP\Template('calendar', 'part.editcalendar');
$tmpl->assign('new', true);
$tmpl->assign('calendarcolor_options', $calendarcolor_options);
$tmpl->assign('calendar', $calendar);
$tmpl->printPage();