<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

if(!OC_USER::isLoggedIn()) {
	die('<script type="text/javascript">document.location = oc_webroot;</script>');
}
OC_JSON::checkAppEnabled('calendar');

$errarr = OC_Calendar_Object::validateRequest($_POST);
if($errarr){
	//show validate errors
	OC_JSON::error($errarr);
	exit;
}else{
	$id = $_POST['id'];
	$cal = $_POST['calendar'];
	$data = OC_Calendar_Object::find($id);
	if (!$data)
	{
		OC_JSON::error();
		exit;
	}
	$calendar = OC_Calendar_Calendar::findCalendar($data['calendarid']);
	if($calendar['userid'] != OC_User::getUser()){
		OC_JSON::error();
		exit;
	}
	$vcalendar = Sabre_VObject_Reader::read($data['calendardata']);
	OC_Calendar_Object::updateVCalendarFromRequest($_POST, $vcalendar);
	$result = OC_Calendar_Object::edit($id, $vcalendar->serialize());
	if ($data['calendarid'] != $cal) {
		OC_Calendar_Object::moveToCalendar($id, $cal);
	}
	OC_JSON::success();
}
?>
