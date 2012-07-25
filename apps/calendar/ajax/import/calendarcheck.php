<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');
$calname = strip_tags($_POST['calname']);
$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser());
foreach($calendars as $calendar){
	if($calendar['displayname'] == $calname){
		OCP\JSON::success(array('message'=>'exists'));
		exit;
	}
}
OCP\JSON::error();