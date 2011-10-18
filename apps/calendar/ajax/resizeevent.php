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

$delta = new DateInterval('P0D');
$delta->d = $_POST['dayDelta'];
$delta->i = $_POST['minuteDelta'];

$vcalendar = Sabre_VObject_Reader::read($data['calendardata']);
$vevent = $vcalendar->VEVENT;

$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
$end_type = $dtend->getDateType();
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
