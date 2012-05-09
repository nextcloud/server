<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
require_once('when/When.php');

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');

$calendar = OC_Calendar_App::getCalendar($_GET['calendar_id'], false, false);
if(is_numeric($calendar['userid']) && $calendar['userid'] != OCP\User::getUser){
	OCP\JSON::error();
	exit;
}

$start = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['start']):new DateTime('@' . $_GET['start']);
$end = (version_compare(PHP_VERSION, '5.3.0', '>='))?DateTime::createFromFormat('U', $_GET['end']):new DateTime('@' . $_GET['end']);

$events = OC_Calendar_App::getrequestedEvents($calendar['id'], $start, $end);

$output = array();
foreach($events as $event){
	$output = array_merge($output, OC_Calendar_App::generateEventOutput($event, $start, $end));
}
OCP\JSON::encodedPrint($output);
