<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$username = $_POST["username"];

if(!OC_Group::inGroup(OC_User::getUser(), 'admin') && OC_SubAdmin::isSubAdmin(OC_User::getUser())){
	$accessiblegroups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$isuseraccessible = false;
	foreach($accessiblegroups as $accessiblegroup){
		if(OC_Group::inGroup($username, $accessiblegroup)){
			$isuseraccessible = true;
			break;
		}
	}
	if(!$isuseraccessible){
		$l = OC_L10N::get('core');
		self::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
		exit();
	}
}

// Return Success story
if( OC_User::deleteUser( $username )){
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to delete user" )));
}
