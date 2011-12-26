<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}
OC_JSON::checkAppEnabled('calendar');

$cal = $_POST["calendarid"];
$calendar = OC_Calendar_App::getCalendar($cal);
$del = OC_Calendar_Calendar::deleteCalendar($cal);
if($del == true){
	OC_JSON::success();
}else{
	OC_JSON::error(array('error'=>'dberror'));
}
?> 
