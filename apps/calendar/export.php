<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once ("../../lib/base.php");
OC_Util::checkLoggedIn();
$cal = $_GET["calid"];
$calendar = OC_Calendar_Calendar::findCalendar($cal);
if($calendar["userid"] != OC_User::getUser()){
	header( 'Location: '.OC_Helper::linkTo('', 'index.php'));
	exit;
}
$calobjects = OC_Calendar_Object::all($cal);
header("Content-Type: text/Calendar");
header("Content-Disposition: inline; filename=calendar.ics"); 
for($i = 0;$i <= count($calobjects); $i++){
	echo $calobjects[$i]["calendardata"] . "\n";
}
?>
