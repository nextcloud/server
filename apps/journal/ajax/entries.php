<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Init owncloud
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('journal');

$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser(), true);
$user_timezone = OCP\Config::getUserValue(OCP\User::getUser(), 'calendar', 'timezone', date_default_timezone_get());
session_write_close();
$journals = array();
foreach( $calendars as $calendar ){
	$calendar_journals = OC_Calendar_Object::all($calendar['id']);
	foreach( $calendar_journals as $journal ) {
		if($journal['objecttype']!='VJOURNAL') {
			continue;
		}
		if(is_null($journal['summary'])) {
			continue;
		}
		$object = OC_VObject::parse($journal['calendardata']);
		$vjournalobj = $object->VJOURNAL;
		try {
			$journals[] = OC_Journal_App::arrayForJSON($journal['id'], $vjournalobj, $user_timezone);
		} catch(Exception $e) {
			OCP\Util::writeLog('journal', 'ajax/getentries.php. id: '.$journal['id'].' '.$e->getMessage(), OCP\Util::ERROR);
		}
	}
}

OCP\JSON::success(array('data' => array('entries' => $journals)));
