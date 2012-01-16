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
	OC_Calendar_Calendar::addCalendar(OC_User::getUser(),'Default calendar');
	$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser(), 1);
}
$eventSources = array();
foreach($calendars as $calendar){
	$eventSources[] = OC_Calendar_Calendar::getEventSourceInfo($calendar);
}
//Fix currentview for fullcalendar
if(OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'currentview', 'month') == "oneweekview"){
	OC_Preferences::setValue(OC_USER::getUser(), "calendar", "currentview", "agendaWeek");
}
if(OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'currentview', 'month') == "onemonthview"){
	OC_Preferences::setValue(OC_USER::getUser(), "calendar", "currentview", "month");
}
if(OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'currentview', 'month') == "listview"){
	OC_Preferences::setValue(OC_USER::getUser(), "calendar", "currentview", "list");
}

OC_Util::addScript('3rdparty/fullcalendar', 'fullcalendar');
OC_Util::addStyle('3rdparty/fullcalendar', 'fullcalendar');
OC_Util::addScript('3rdparty/timepicker', 'jquery.ui.timepicker');
OC_Util::addStyle('3rdparty/timepicker', 'jquery.ui.timepicker');
if(OC_Preferences::getValue(OC_USER::getUser(), "calendar", "timezone") == null || OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezonedetection') == 'true'){
	OC_UTIL::addScript('calendar', 'geo');
}
OC_Util::addScript('calendar', 'calendar');
OC_Util::addStyle('calendar', 'style');
OC_Util::addScript('', 'jquery.multiselect');
OC_Util::addStyle('', 'jquery.multiselect');
OC_App::setActiveNavigationEntry('calendar_index');
$tmpl = new OC_Template('calendar', 'calendar', 'user');
$tmpl->assign('eventSources', $eventSources);
if(array_key_exists('showevent', $_GET)){
	$tmpl->assign('showevent', $_GET['showevent']);
}
$tmpl->printPage();
