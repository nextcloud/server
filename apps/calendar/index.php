<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ('../../lib/base.php');
OC_Util::checkLoggedIn();
// Create default calendar ...
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
if( count($calendars) == 0){
	OC_Calendar_Calendar::addCalendar(OC_User::getUser(),'default','Default calendar');
	$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
}
OC_UTIL::addScript('calendar', 'calendar');
OC_UTIL::addStyle('calendar', 'style');
OC_UTIL::addScript('', 'jquery.multiselect');
OC_UTIL::addStyle('', 'jquery.multiselect');
OC_APP::setActiveNavigationEntry('calendar_index');
$output = new OC_TEMPLATE('calendar', 'calendar', 'user');
$output -> printPage();
