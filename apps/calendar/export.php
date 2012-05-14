<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');
$cal = isset($_GET['calid']) ? $_GET['calid'] : NULL;
$event = isset($_GET['eventid']) ? $_GET['eventid'] : NULL;
$nl = "\r\n";
if(isset($cal)){
	$calendar = OC_Calendar_App::getCalendar($cal, true);
	if(!$calendar){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	$calobjects = OC_Calendar_Object::all($cal);
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . $calendar['displayname'] . '.ics'); 
	foreach($calobjects as $calobject){
		echo $calobject['calendardata'] . $nl;
	}
}elseif(isset($event)){
	$data = OC_Calendar_App::getEventObject($_GET['eventid'], true);
	if(!$data){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	$calendarid = $data['calendarid'];
	$calendar = OC_Calendar_App::getCalendar($calendarid);
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . $data['summary'] . '.ics'); 
	echo $data['calendardata'];
}
?>
