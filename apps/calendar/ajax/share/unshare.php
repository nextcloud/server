<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../../lib/base.php');
$id = strip_tags($_GET['id']);
$idtype = strip_tags($_GET['idtype']);
switch($idtype){
	case 'calendar':
	case 'event':
		break;
	default:
		OC_JSON::error(array('message'=>'unexspected parameter'));
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
		OC_JSON::error(array('message'=>'unexspected parameter'));
		exit;
}
if($sharetype == 'user' && !OC_User::userExists($sharewith)){
	OC_JSON::error(array('message'=>'user not found'));
	exit;
}elseif($sharetype == 'group' && !OC_Group::groupExists($sharewith)){
	OC_JSON::error(array('message'=>'group not found'));
	exit;
}
$success = OC_Calendar_Share::unshare(OC_User::getUser(), $sharewith, $sharetype, $id, (($idtype=='calendar') ? OC_Calendar_Share::CALENDAR : OC_Calendar_Share::EVENT));
if($success){
	OC_JSON::success();
}else{
	OC_JSON::error(array('message'=>'can not unshare'));
	exit;
}