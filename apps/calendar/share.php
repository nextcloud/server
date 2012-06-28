<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
$token = strip_tags($_GET['t']);
$shared = OC_Calendar_Share::getElementByToken($token);
if($shared['type'] == OC_Calendar_Share::CALENDAR){
	$calendar = OC_Calendar_App::getCalendar($shared['id'], false);
	if(!$calendar){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '-', $calendar['displayname']) . '.ics'); 
	echo OC_Calendar_Export::export($shared['id'], OC_Calendar_Export::CALENDAR);
}elseif($shared['type'] == OC_Calendar_Share::EVENT){
	$data = OC_Calendar_App::getEventObject($shared['id'], false);
	if(!$data){
		header('HTTP/1.0 404 Not Found');
		exit;
	}
	header('Content-Type: text/Calendar');
	header('Content-Disposition: inline; filename=' . str_replace(' ', '-', $data['summary']) . '.ics');
	echo OC_Calendar_Export::export($shared['id'], OC_Calendar_Export::EVENT);
}else{
	header('HTTP/1.0 404 Not Found');
	exit;
}
