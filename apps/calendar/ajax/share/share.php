<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 OCP\JSON::callCheck();

$id = strip_tags($_GET['id']);
$idtype = strip_tags($_GET['idtype']);
switch($idtype){
	case 'calendar':
	case 'event':
		break;
	default:
		OCP\JSON::error(array('message'=>'unexpected parameter'));
		exit;
}
if($idtype == 'calendar' && !OC_Calendar_App::getCalendar($id)){
	OCP\JSON::error(array('message'=>'permission denied'));
	exit;
}
if($idtype == 'event' && !OC_Calendar_App::getEventObject($id)){
	OCP\JSON::error(array('message'=>'permission denied'));
	exit;
}
$sharewith = $_GET['sharewith'];
$sharetype = strip_tags($_GET['sharetype']);
switch($sharetype){
	case 'user':
	case 'group':
	case 'public':
		break;
	default:
		OCP\JSON::error(array('message'=>'unexpected parameter'));
		exit;
}
if($sharetype == 'user' && !OCP\User::userExists($sharewith)){
	OCP\JSON::error(array('message'=>'user not found'));
	exit;
}
if($sharetype == 'group' && !OC_Group::groupExists($sharewith)){
	OCP\JSON::error(array('message'=>'group not found'));
	exit;
}
if($sharetype == 'user' && OCP\USER::getUser() == $sharewith){
	OCP\JSON::error(array('message'=>'you can not share with yourself'));
}
$success = OC_Calendar_Share::share(OCP\USER::getUser(), $sharewith, $sharetype, $id, (($idtype=='calendar') ? OC_Calendar_Share::CALENDAR : OC_Calendar_Share::EVENT));
if($success){
	if($sharetype == 'public'){
		OCP\JSON::success(array('message'=>$success));
	}else{
		OCP\JSON::success(array('message'=>'shared'));
	}
}else{
	OCP\JSON::error(array('message'=>'can not share'));
	exit;
}