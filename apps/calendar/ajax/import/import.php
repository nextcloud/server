<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');
OCP\JSON::callCheck();
session_write_close();
if (isset($_POST['progresskey']) && isset($_POST['getprogress'])) {
	echo OCP\JSON::success(array('percent'=>OC_Cache::get($_POST['progresskey'])));
	exit;
}
$file = OC_Filesystem::file_get_contents($_POST['path'] . '/' . $_POST['file']);
if(!$file){
	OCP\JSON::error(array('error'=>'404'));
}
$import = new OC_Calendar_Import($file);
$import->setUserID(OCP\User::getUser());
$import->setTimeZone(OC_Calendar_App::$tz);
$import->enableProgressCache();
$import->setProgresskey($_POST['progresskey']);
if(!$import->isValid()){
	OCP\JSON::error(array('error'=>'notvalid'));
	exit;
}
$newcal = false;
if($_POST['method'] == 'new'){
	$calendars = OC_Calendar_Calendar::allCalendars(OCP\User::getUser());
	foreach($calendars as $calendar){
		if($calendar['displayname'] == $_POST['calname']){
			$id = $calendar['id'];
			$newcal = false;
			break;
		}
		$newcal = true;
	}
	if($newcal){
		$id = OC_Calendar_Calendar::addCalendar(OCP\USER::getUser(), strip_tags($_POST['calname']),'VEVENT,VTODO,VJOURNAL',null,0,strip_tags($_POST['calcolor']));
		OC_Calendar_Calendar::setCalendarActive($id, 1);
	}
}else{
	$calendar = OC_Calendar_App::getCalendar($_POST['id']);
	if($calendar['userid'] != OCP\USER::getUser()){
		OCP\JSON::error(array('error'=>'missingcalendarrights'));
		exit();
	}
	$id = $_POST['id'];
}
$import->setCalendarID($id);
try{
	$import->import();
}catch (Exception $e) {
	OCP\JSON::error(array('message'=>OC_Calendar_App::$l10n->t('Import failed'), 'debug'=>$e->getMessage()));
	//write some log
}
$count = $import->getCount();
if($count == 0){
	if($newcal){
		OC_Calendar_Calendar::deleteCalendar($id);
	}
	OCP\JSON::error(array('message'=>OC_Calendar_App::$l10n->t('The file contained either no events or all events are already saved in your calendar.')));
}else{
	if($newcal){
		OCP\JSON::success(array('message'=>$count . ' ' . OC_Calendar_App::$l10n->t('events has been saved in the new calendar') . ' ' .  strip_tags($_POST['calname'])));
	}else{
		OCP\JSON::success(array('message'=>$count . ' ' . OC_Calendar_App::$l10n->t('events has been saved in your calendar')));
	}
}
/*		//////////////////////////// Attention: following code is quite painfull !!! ///////////////////////
writeProgress('20');
// normalize the newlines
$file = str_replace(array("\r","\n\n"), array("\n","\n"), $file);
$lines = explode("\n", $file);
unset($file);
writeProgress('30');
// analyze the file, group components by uid, and keep refs to originating calendar object
// $cals is array calendar objects, keys are 1st line# $cal, ie array( $cal => $caldata )
//   $caldata is array( 'first' => 1st component line#, 'last' => last comp line#, 'end' => end line# )
//   $caldata is used to create prefix/suffix strings when building import text
// $uids is array of component arrays, keys are $uid, ie array( $uid => array( $beginlineno => $component ) )
//   $component is array( 'end' => end line#, 'cal'=> $cal )
$comp=$uid=$cal=false;
$cals=$uids=array();
$i = 0;
foreach($lines as $line) {

	if(strpos($line, ':')!==false) {
		list($attr, $val) = explode(':', strtoupper($line));
		if ($attr == 'BEGIN' && $val == 'VCALENDAR') {
			$cal = $i;
			$cals[$cal] = array('first'=>$i,'last'=>$i,'end'=>$i);
		} elseif ($attr =='BEGIN' && $cal!==false && isset($comps[$val])) {
			$comp = $val;
			$beginNo = $i;
		} elseif ($attr == 'END' && $cal!==false && $val == 'VCALENDAR') {
			if($comp!==false) {
				unset($cals[$cal]); // corrupt calendar, unset it
			} else {
				$cals[$cal]['end'] = $i;
			}
			$comp=$uid=$cal=false; // reset calendar
		} elseif ($attr == 'END' && $comp!==false && $val == $comp) {
			if(! $uid) {
				$uid = OC_Calendar_Object::createUID();
			}
			$uids[$uid][$beginNo] = array('end'=>$i, 'cal'=>$cal);
			if ($cals[$cal]['first'] == $cal) {
				$cals[$cal]['first'] = $beginNo;
			}
			$cals[$cal]['last'] = $i;
			$comp=$uid=false; // reset component
		} elseif ($attr =="UID" && $comp!==false) {
			list($attr, $uid) = explode(':', $line);
		}
	}
	$i++;
}
// import the calendar
writeProgress('60');
foreach($uids as $uid) {
	$prefix=$suffix=$content=array();
	foreach($uid as $begin=>$details) {
		
		$cal = $details['cal'];
		if(!isset($cals[$cal])) {
			continue; // from corrupt/incomplete calendar
		}
		$cdata = $cals[$cal];
		// if we have multiple components from different calendar objects,
		// we should really merge their elements (enhancement?) -- 1st one wins for now.
		if(! count($prefix)) {
			$prefix = array_slice($lines, $cal, $cdata['first'] - $cal);
		}
		if(! count($suffix)) {
			$suffix = array_slice($lines, $cdata['last']+1, $cdata['end'] - $cdata['last']);
		}
		$content = array_merge($content, array_slice($lines, $begin, $details['end'] - $begin + 1));
	}
	if(count($content)) {
		$import = join($nl, array_merge($prefix, $content, $suffix)) . $nl;
		OC_Calendar_Object::add($id, $import);
	}
}
// finished import
writeProgress('100');
sleep(3);
OC_Cache::remove($progresskey);
OCP\JSON::success();*/
