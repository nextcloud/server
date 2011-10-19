<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ('../../lib/base.php');
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('calendar');
// Create default calendar ...
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
if( count($calendars) == 0){
	OC_Calendar_Calendar::addCalendar(OC_User::getUser(),'default','Default calendar');
	$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
}
$eventSources = array();
foreach($calendars as $calendar){
	$eventSources[] = OC_Calendar_Calendar::getEventSourceInfo($calendar);
}
OC_Util::addScript('calendar', 'calendar');
OC_Util::addStyle('calendar', 'style');
OC_Util::addScript('', 'jquery.multiselect');
OC_Util::addStyle('', 'jquery.multiselect');
OC_Util::addScript('3rdparty/fullcalendar', 'fullcalendar');
OC_Util::addStyle('3rdparty/fullcalendar', 'fullcalendar');
OC_App::setActiveNavigationEntry('calendar_index');
$tmpl = new OC_Template('calendar', 'calendar', 'user');
$tmpl->assign('eventSources', $eventSources);
$tmpl->printPage();
