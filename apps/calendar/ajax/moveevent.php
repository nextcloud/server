<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
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
$id = $_POST["id"];
$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
if(OC_User::getUser() != $calendar["userid"]){
	OC_JSON::error();
	exit;
}
$newdate = $_POST["newdate"];
$caldata = array();
//modified part of editeventform.php
$object = Sabre_VObject_Reader::read($data['calendardata']);
$vevent = $object->VEVENT;
$dtstart = $vevent->DTSTART;
$dtend = OC_Calendar_Object::getDTEndFromVEvent($vevent);
switch($dtstart->getDateType()) {
	case Sabre_VObject_Element_DateTime::LOCALTZ:
	case Sabre_VObject_Element_DateTime::LOCAL:
		$startdate = $dtstart->getDateTime()->format('d-m-Y');
		$starttime = $dtstart->getDateTime()->format('H:i');
		$enddate = $dtend->getDateTime()->format('d-m-Y');
		$endtime = $dtend->getDateTime()->format('H:i');
		$allday = false;
		break;
	case Sabre_VObject_Element_DateTime::DATE:
		$startdate = $dtstart->getDateTime()->format('d-m-Y');
		$starttime = '00:00';
		$dtend->getDateTime()->modify('-1 day');
		$enddate = $dtend->getDateTime()->format('d-m-Y');
		$endtime = '23:59';
		$allday = true;
		break;
}
$caldata["title"] = isset($vevent->SUMMARY) ? $vevent->SUMMARY->value : '';
$caldata["location"] = isset($vevent->LOCATION) ? $vevent->LOCATION->value : '';
$caldata["categories"] = array();
if (isset($vevent->CATEGORIES)){
       $caldata["categories"] = explode(',', $vevent->CATEGORIES->value);
       $caldata["categories"] = array_map('trim', $categories);
}
foreach($caldata["categories"] as $category){
	if (!in_array($category, $category_options)){
		array_unshift($category_options, $category);
	}
}
$caldata["repeat"] = isset($vevent->CATEGORY) ? $vevent->CATEGORY->value : '';
$caldata["description"] = isset($vevent->DESCRIPTION) ? $vevent->DESCRIPTION->value : '';
//end part of editeventform.php
$startdatearray = explode("-", $startdate);
$starttimearray = explode(":", $starttime);
$startunix = mktime($starttimearray[0], $starttimearray[1], 0, $startdatearray[1], $startdatearray[0], $startdatearray[2]);
$enddatearray = explode("-", $enddate);
$endtimearray = explode(":", $endtime);
$endunix = mktime($endtimearray[0], $endtimearray[1], 0, $enddatearray[1], $enddatearray[0], $enddatearray[2]);
$difference = $endunix - $startunix;
if(strlen($newdate) > 10){
	$newdatestringarray = explode("-", $newdate);
	if($newdatestringarray[1] == "allday"){
		$allday = true;
		$newdatestringarray[1] = "00:00";
	}else{
		$allday = false;
	}
}else{
	$newdatestringarray = array();
	$newdatestringarray[0] = $newdate;
	$newdatestringarray[1] = $starttime;
}
$newdatearray = explode(".", $newdatestringarray[0]);
$newtimearray = explode(":", $newdatestringarray[1]);
$newstartunix = mktime($newtimearray[0], $newtimearray[1], 0, $newdatearray[1], $newdatearray[0], $newdatearray[2]);
$newendunix = $newstartunix + $difference;
if($allday == true){
	$caldata["allday"] = true;
}else{
	unset($caldata["allday"]);
}
$caldata["from"] = date("d-m-Y", $newstartunix);
$caldata["fromtime"] = date("H:i", $newstartunix);
$caldata["to"]  = date("d-m-Y", $newendunix);
$caldata["totime"] = date("H:i", $newendunix);
//modified part of editevent.php
$vcalendar = Sabre_VObject_Reader::read($data["calendardata"]);
OC_Calendar_Object::updateVCalendarFromRequest($caldata, $vcalendar);

$result = OC_Calendar_Object::edit($id, $vcalendar->serialize());
OC_JSON::success();
//end part of editevent.php
?>