<?php
/**
 * Copyright (c) 2012 Georg Ehrke <georg@ownCloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
foreach($calendars as $calendar){
	OC_Calendar_Repeat::cleancalendar($calendar['id']);
	OC_Calendar_Repeat::generatecalendar($calendar['id']);
}
OCP\JSON::success();