<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$success = true;
$error = "add user to";
$action = "add";

$username = $_POST["username"];
$group = htmlentities($_POST["group"]);

if(!OC_Group::groupExists($group)){
	OC_Group::createGroup($group);
}

// Toggle group
if( OC_Group::inGroup( $username, $group )){
	$action = "remove";
	$error = "remove user from";
	$success = OC_Group::removeFromGroup( $username, $group );
	$usersInGroup=OC_Group::usersInGroup($group);
	if(count($usersInGroup)==0){
		OC_Group::deleteGroup($group);
	}
}
else{
	$success = OC_Group::addToGroup( $username, $group );
}

// Return Success story
if( $success ){
	OC_JSON::success(array("data" => array( "username" => $username, "action" => $action, "groupname" => $group )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to $error group $group" )));
}

?>
