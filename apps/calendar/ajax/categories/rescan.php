<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.print_r($element, true));
}

function bailOut($msg) {
	OCP\JSON::error(array('data' => array('message' => $msg)));
	OCP\Util::writeLog('calendar','ajax/categories/rescan.php: '.$msg, OCP\Util::DEBUG);
	exit();
}
function debug($msg) {
	OCP\Util::writeLog('calendar','ajax/categories/rescan.php: '.$msg, OCP\Util::DEBUG);
}

$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
if(count($calendars) == 0) {
	bailOut(OC_Calendar_App::$l10n->t('No calendars found.'));
}
$events = array();
foreach($calendars as $calendar) {
	$calendar_events = OC_Calendar_Object::all($calendar['id']);
	$events = $events + $calendar_events;
}
if(count($events) == 0) {
	bailOut(OC_Calendar_App::$l10n->t('No events found.'));
}

OC_Calendar_App::scanCategories($events);
$categories = OC_Calendar_App::getCategoryOptions();

OCP\JSON::success(array('data' => array('categories'=>$categories)));