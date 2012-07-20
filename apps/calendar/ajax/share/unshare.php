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
		OCP\JSON::error(array('message'=>'unexspected parameter'));
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
		OCP\JSON::error(array('message'=>'unexspected parameter'));
		exit;
}
if($sharetype == 'user' && !OCP\User::userExists($sharewith)){
	OCP\JSON::error(array('message'=>'user not found'));
	exit;
}elseif($sharetype == 'group' && !OC_Group::groupExists($sharewith)){
	OCP\JSON::error(array('message'=>'group not found'));
	exit;
}
$success = OC_Calendar_Share::unshare(OCP\USER::getUser(), $sharewith, $sharetype, $id, (($idtype=='calendar') ? OC_Calendar_Share::CALENDAR : OC_Calendar_Share::EVENT));
if($success){
	OCP\JSON::success();
}else{
	OCP\JSON::error(array('message'=>'can not unshare'));
	exit;
}