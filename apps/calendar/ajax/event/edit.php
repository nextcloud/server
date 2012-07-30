<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
OCP\JSON::callCheck();

$id = $_POST['id'];

if(!array_key_exists('calendar', $_POST)){
	$cal = OC_Calendar_Object::getCalendarid($id);
	$_POST['calendar'] = $cal;
}else{
	$cal = $_POST['calendar'];
}

$access = OC_Calendar_App::getaccess($id, OC_Calendar_App::EVENT);
if($access != 'owner' && $access != 'rw'){
	OCP\JSON::error(array('message'=>'permission denied'));
	exit;
}

$errarr = OC_Calendar_Object::validateRequest($_POST);
if($errarr){
	//show validate errors
	OCP\JSON::error($errarr);
	exit;
}else{
	$data = OC_Calendar_App::getEventObject($id, false, false);
	$vcalendar = OC_VObject::parse($data['calendardata']);

	OC_Calendar_App::isNotModified($vcalendar->VEVENT, $_POST['lastmodified']);
	OC_Calendar_Object::updateVCalendarFromRequest($_POST, $vcalendar);

	OC_Calendar_Object::edit($id, $vcalendar->serialize());
	if ($data['calendarid'] != $cal) {
		OC_Calendar_Object::moveToCalendar($id, $cal);
	}
	OCP\JSON::success();
}