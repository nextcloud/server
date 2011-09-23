<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}

$cal = $_POST["calendarid"];
$calendar = OC_Calendar_Calendar::findCalendar($cal);
if($calendar["userid"] != OC_User::getUser()){
	OC_JSON::error(array('error'=>'permission_denied'));
	exit;
}
$del = OC_Calendar_Calendar::deleteCalendar($cal);
if($del == true){
	OC_JSON::success();
}else{
	OC_JSON::error(array('error'=>'dberror'));
}
?> 
