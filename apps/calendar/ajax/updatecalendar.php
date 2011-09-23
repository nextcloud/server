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

$calendarid = $_POST['id'];
OC_Calendar_Calendar::editCalendar($calendarid, $_POST['name'], $_POST['description'], null, null, null, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, $_POST['active']);
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
OC_JSON::success(array('data' => $tmpl->fetchPage()));
