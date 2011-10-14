<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../lib/base.php");
OC_Util::checkLoggedIn();
OC_Util::checkAppEnabled('calendar');
$cal = isset($_GET["calid"]) ? $_GET["calid"] : NULL;
$event = isset($_GET["eventid"]) ? $_GET["eventid"] : NULL;
if(isset($cal)){
	$calendar = OC_Calendar_Calendar::findCalendar($cal);
	if($calendar["userid"] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	$calobjects = OC_Calendar_Object::all($cal);
	header("Content-Type: text/Calendar");
	header("Content-Disposition: inline; filename=calendar.ics"); 
	for($i = 0;$i <= count($calobjects); $i++){
		echo $calobjects[$i]["calendardata"] . "\n";
	}
}elseif(isset($event)){
	$data = OC_Calendar_Object::find($_GET["eventid"]);
	$calendarid = $data["calendarid"];
	$calendar = OC_Calendar_Calendar::findCalendar($calendarid);
	if($calendar["userid"] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	header("Content-Type: text/Calendar");
	header("Content-Disposition: inline; filename=" . $data["summary"] . ".ics"); 
	echo $data["calendardata"];
}
?>
