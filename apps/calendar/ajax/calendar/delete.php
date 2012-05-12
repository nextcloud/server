<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');

$cal = $_POST["calendarid"];
$calendar = OC_Calendar_App::getCalendar($cal);
$del = OC_Calendar_Calendar::deleteCalendar($cal);
if($del == true){
	OCP\JSON::success();
}else{
	OCP\JSON::error(array('error'=>'dberror'));
}
?> 
