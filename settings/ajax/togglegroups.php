<?php

OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$success = true;
$username = $_POST["username"];
$group = $_POST["group"];

if($username === OC_User::getUser() && $group === "admin" &&  OC_User::isAdminUser($username)) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Admins can\'t remove themself from the admin group'))));
	exit();
}

if(!OC_User::isAdminUser(OC_User::getUser())
	&& (!OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)
		|| !OC_SubAdmin::isGroupAccessible(OC_User::getUser(), $group))) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
	exit();
}

if(!OC_Group::groupExists($group)) {
	OC_Group::createGroup($group);
}

$l = \OC::$server->getL10N('settings');

$error = $l->t("Unable to add user to group %s", $group);
$action = "add";

// Toggle group
if( OC_Group::inGroup( $username, $group )) {
	$action = "remove";
	$error = $l->t("Unable to remove user from group %s", $group);
	$success = OC_Group::removeFromGroup( $username, $group );
	$usersInGroup=OC_Group::usersInGroup($group);
	if(count($usersInGroup) === 0) {
		OC_Group::deleteGroup($group);
	}
}
else{
	$success = OC_Group::addToGroup( $username, $group );
}

// Return Success story
if( $success ) {
	OC_JSON::success(array("data" => array( "username" => $username, "action" => $action, "groupname" => $group )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $error )));
}
