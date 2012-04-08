<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$id = $_POST['id'];

$access = OC_Calendar_App::getaccess($id, OC_Calendar_App::EVENT);
if($access != 'owner' && $access != 'rw'){
	OC_JSON::error(array('message'=>'permission denied'));
	exit;
}

$errarr = OC_Calendar_Object::validateRequest($_POST);
if($errarr){
	//show validate errors
	OC_JSON::error($errarr);
	exit;
}else{
	$cal = $_POST['calendar'];
	$data = OC_Calendar_App::getEventObject($id);
	$vcalendar = OC_VObject::parse($data['calendardata']);

	OC_Calendar_App::isNotModified($vcalendar->VEVENT, $_POST['lastmodified']);
	OC_Calendar_Object::updateVCalendarFromRequest($_POST, $vcalendar);

	$result = OC_Calendar_Object::edit($id, $vcalendar->serialize());
	if ($data['calendarid'] != $cal) {
		OC_Calendar_Object::moveToCalendar($id, $cal);
	}
	OC_JSON::success();
}
?>
