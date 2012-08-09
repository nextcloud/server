<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
DEFINE('DEBUG', TRUE);
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');

// Create default calendar ...
$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser(), false);
if( count($calendars) == 0){
	OC_Calendar_Calendar::addCalendar(OCP\USER::getUser(),'Default calendar');
	$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser(), true);
}

$eventSources = array();
foreach($calendars as $calendar){
	if($calendar['active'] == 1) {
		$eventSources[] = OC_Calendar_Calendar::getEventSourceInfo($calendar);
	}
}

$events_baseURL = OCP\Util::linkTo('calendar', 'ajax/events.php');
$eventSources[] = array('url' => $events_baseURL.'?calendar_id=shared_rw',
		'backgroundColor' => '#1D2D44',
		'borderColor' => '#888',
		'textColor' => 'white',
		'editable'=>'true');
$eventSources[] = array('url' => $events_baseURL.'?calendar_id=shared_r',
		'backgroundColor' => '#1D2D44',
		'borderColor' => '#888',
		'textColor' => 'white',
		'editable' => 'false');

OCP\Util::emitHook('OC_Calendar', 'getSources', array('sources' => &$eventSources));
$categories = OC_Calendar_App::getCategoryOptions();

//Fix currentview for fullcalendar
if(OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'currentview', 'month') == "oneweekview"){
	OCP\Config::setUserValue(OCP\USER::getUser(), "calendar", "currentview", "agendaWeek");
}
if(OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'currentview', 'month') == "onemonthview"){
	OCP\Config::setUserValue(OCP\USER::getUser(), "calendar", "currentview", "month");
}
if(OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'currentview', 'month') == "listview"){
	OCP\Config::setUserValue(OCP\USER::getUser(), "calendar", "currentview", "list");
}

OCP\Util::addscript('3rdparty/fullcalendar', 'fullcalendar');
OCP\Util::addStyle('3rdparty/fullcalendar', 'fullcalendar');
OCP\Util::addscript('3rdparty/timepicker', 'jquery.ui.timepicker');
OCP\Util::addStyle('3rdparty/timepicker', 'jquery.ui.timepicker');
if(OCP\Config::getUserValue(OCP\USER::getUser(), "calendar", "timezone") == null || OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timezonedetection') == 'true'){
	OCP\Util::addscript('calendar', 'geo');
}
OCP\Util::addscript('calendar', 'calendar');
OCP\Util::addStyle('calendar', 'style');
OCP\Util::addscript('', 'jquery.multiselect');
OCP\Util::addStyle('', 'jquery.multiselect');
OCP\Util::addscript('contacts','jquery.multi-autocomplete');
OCP\Util::addscript('','oc-vcategories');
OCP\App::setActiveNavigationEntry('calendar_index');
$tmpl = new OCP\Template('calendar', 'calendar', 'user');
$tmpl->assign('eventSources', $eventSources,false);
$tmpl->assign('categories', $categories);
if(array_key_exists('showevent', $_GET)){
	$tmpl->assign('showevent', $_GET['showevent'], false);
}
$tmpl->printPage();
