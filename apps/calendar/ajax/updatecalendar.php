<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => $l->t("Authentication error") )));
	exit();
}

$calendarid = $_POST['id'];
OC_Calendar_Calendar::editCalendar($calendarid, $_POST['name'], $_POST['description'], null, null, null, $_POST['color']);
OC_Calendar_Calendar::setCalendarActive($calendarid, $_POST['active']);
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
$tmpl->assign('calendar', $calendar);
echo json_encode( array( "status" => "success", "data" => $tmpl->fetchPage() ));
