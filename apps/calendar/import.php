<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
//check for calendar rights or create new one
ob_start();
require_once ('../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_Util::checkAppEnabled('calendar');
$nl = "\n";
$progressfile = 'import_tmp/' . md5(session_id()) . '.txt';
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '10');
	fclose($progressfopen);
}
$file = OC_Filesystem::file_get_contents($_POST['path'] . '/' . $_POST['file']);
if($_POST['method'] == 'new'){
	$id = OC_Calendar_Calendar::addCalendar(OC_User::getUser(), $_POST['calname']);
	OC_Calendar_Calendar::setCalendarActive($id, 1);
}else{
	$calendar = OC_Calendar_App::getCalendar($_POST['id']);
	if($calendar['userid'] != OC_USER::getUser()){
		OC_JSON::error();
		exit();
	}
	$id = $_POST['id'];
}
//analyse the calendar file
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '20');
	fclose($progressfopen);
}
$searchfor = array('VEVENT', 'VTODO', 'VJOURNAL');
$parts = $searchfor;
$filearr = explode($nl, $file);
$inelement = false;
$parts = array();
$i = 0;
foreach($filearr as $line){
	foreach($searchfor as $search){
		if(substr_count($line, $search) == 1){
			list($attr, $val) = explode(':', $line);
			if($attr == 'BEGIN'){
				$parts[]['begin'] = $i;
				$inelement = true;
			}
			if($attr == 'END'){
				$parts[count($parts) - 1]['end'] = $i;
				$inelement = false;
			}
		}
	}
	$i++;
}
//import the calendar
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '40');
	fclose($progressfopen);
}
$start = '';
for ($i = 0; $i < $parts[0]['begin']; $i++) { 
	if($i == 0){
		$start = $filearr[0];
	}else{
		$start .= $nl . $filearr[$i];
	}
}
$end = '';
for($i = $parts[count($parts) - 1]['end'] + 1;$i <= count($filearr) - 1; $i++){
	if($i == $parts[count($parts) - 1]['end'] + 1){
		$end = $filearr[$parts[count($parts) - 1]['end'] + 1];
	}else{
		$end .= $nl . $filearr[$i];
	}
}
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '50');
	fclose($progressfopen);
}
$importready = array();
foreach($parts as $part){
	for($i = $part['begin']; $i <= $part['end'];$i++){
		if($i == $part['begin']){
			$content = $filearr[$i];
		}else{
			$content .= $nl . $filearr[$i];
		}
	}
	$importready[] = $start . $nl . $content . $nl . $end;
}
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '70');
	fclose($progressfopen);
}
if(count($parts) == 1){
	OC_Calendar_Object::add($id, $file);
}else{
	foreach($importready as $import){
		OC_Calendar_Object::add($id, $import);
	}
}
//done the import
if(is_writable('import_tmp/')){
	$progressfopen = fopen($progressfile, 'w');
	fwrite($progressfopen, '100');
	fclose($progressfopen);
}
sleep(3);
if(is_writable('import_tmp/')){
	unlink($progressfile);
}
OC_JSON::success();