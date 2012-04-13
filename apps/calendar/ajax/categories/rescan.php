<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

foreach ($_POST as $key=>$element) {
	debug('_POST: '.$key.'=>'.print_r($element, true));
}

function bailOut($msg) {
	OC_JSON::error(array('data' => array('message' => $msg)));
	OC_Log::write('calendar','ajax/categories/rescan.php: '.$msg, OC_Log::DEBUG);
	exit();
}
function debug($msg) {
	OC_Log::write('calendar','ajax/categories/rescan.php: '.$msg, OC_Log::DEBUG);
}

$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
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

OC_JSON::success(array('data' => array('categories'=>$categories)));
