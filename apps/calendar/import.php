<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ('../../lib/base.php');
//OC_JSON::checkLoggedIn();
OC_Util::checkAppEnabled('calendar');

if($_GET["import"] == "existing"){
	$calid = $_GET["calid"];
	$calendar = OC_Calendar_Calendar::findCalendar($calid);
	if($calendar['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	if($_GET["path"] != ""){
		$filename = $_GET["path"] . "/" . $_GET["file"];
	}else{
		$filename = "/" . $_GET["file"];
	}
}else{
	$id = OC_Calendar_Calendar::addCalendar(OC_User::getUser(), $_POST['calname'], $_POST['description']);
	OC_Calendar_Calendar::setCalendarActive($id, 1);
	$calid = $id;
	if($_POST["path"] != ""){
		$filename = $_POST["path"] . "/" . $_POST["file"];
	}else{
		$filename = "/" . $_POST["file"];
	}
}
$vcalendar = OC_Filesystem::file_get_contents($filename);
$vcalendar = explode("BEGIN:VEVENT", $vcalendar);
for($i = 1;$i < count($vcalendar);$i++){
	$vcalendar[$i] = "BEGIN:VEVENT" . $vcalendar[$i];
}
for($i = 1;$i < count($vcalendar) - 1;$i++){
	$vcalendar[$i] = $vcalendar[$i] . "END:VCALENDAR";
}
for($i = 1;$i < count($vcalendar);$i++){
	$vcalendar[$i] = $vcalendar[0] . $vcalendar[$i];
}
for($i = 1;$i < count($vcalendar);$i++){
	OC_Calendar_Object::add($calid, $vcalendar[$i]);
}
OC_JSON::success();
?>