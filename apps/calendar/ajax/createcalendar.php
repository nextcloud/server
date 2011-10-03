<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

// Check if we are a user
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$userid = OC_User::getUser();
$calendarid = OC_Calendar_Calendar::addCalendar($userid, $_POST['name'], $_POST['description'], 'VEVENT,VTODO,VJOURNAL', null, 0, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, 1);
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
OC_JSON::success(array('data' => $tmpl->fetchPage()));
