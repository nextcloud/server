<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
error_reporting(E_ALL);
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$data = OC_Calendar_Object::find($_POST["id"]);
$calendarid = $data["calendarid"];
$cal = $calendarid;
$id = $_POST['id'];
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
if(OC_User::getUser() != $calendar['userid']){
	OC_JSON::error();
	exit;
}
$allday = $_POST['allDay'];
$delta = new DateInterval('P0D');
$delta->d = $_POST['dayDelta'];
$delta->i = $_POST['minuteDelta'];

$vcalendar = OC_Calendar_Object::parse($data['calendardata']);
$vevent = $vcalendar->VEVENT;

$dtstart = $vevent->DTSTART;
$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
$start_type = $dtstart->getDateType();
$end_type = $dtend->getDateType();
if ($allday && $start_type != Sabre_VObject_Element_DateTime::DATE){
	$start_type = $end_type = Sabre_VObject_Element_DateTime::DATE;
	$dtend->setDateTime($dtend->getDateTime()->modify('+1 day'), $end_type);
}
if (!$allday && $start_type == Sabre_VObject_Element_DateTime::DATE){
	$start_type = $end_type = Sabre_VObject_Element_DateTime::LOCALTZ;
}
$dtstart->setDateTime($dtstart->getDateTime()->add($delta), $start_type);
$dtend->setDateTime($dtend->getDateTime()->add($delta), $end_type);
unset($vevent->DURATION);

$now = new DateTime();
$last_modified = new Sabre_VObject_Element_DateTime('LAST-MODIFIED');
$last_modified->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
$vevent->__set('LAST-MODIFIED', $last_modified);

$dtstamp = new Sabre_VObject_Element_DateTime('DTSTAMP');
$dtstamp->setDateTime($now, Sabre_VObject_Element_DateTime::UTC);
$vevent->DTSTAMP = $dtstamp;

$result = OC_Calendar_Object::edit($id, $vcalendar->serialize());
OC_JSON::success();
