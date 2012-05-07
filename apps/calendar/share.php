<?php
$token = strip_tags($_GET['t']);
$shared = OC_Calendar_Share::getElementByToken($token);
$nl = "\n\r";
if($shared['type'] == OC_Calendar_Share::CALENDAR){
	$calendar = OC_Calendar_App::getCalendar($shared['id'], false);
	$calobjects = OC_Calendar_Object::all($shared['id']);
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . $calendar['displayname'] . '.ics'); 
	foreach($calobjects as $calobject){
		echo $calobject['calendardata'] . $nl;
	}
}elseif($shared['type'] == OC_Calendar_Share::EVENT){
	$data = OC_Calendar_App::getEventObject($shared['id'], false);
	$calendarid = $data['calendarid'];
	$calendar = OC_Calendar_App::getCalendar($calendarid);
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . $data['summary'] . '.ics'); 
	echo $data['calendardata'];
}else{
	header('Error 404: Not Found');
}