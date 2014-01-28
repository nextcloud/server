<?php

OCP\JSON::callCheck();
OC_JSON::checkAdminUser();

$groupname = $_POST["groupname"];

// Does the group exist?
if( in_array( $groupname, OC_Group::getGroups())) {
	OC_JSON::error(array("data" => array( "message" => 'Group already exists' )));
	exit();
}

// Return Success story
if( OC_Group::createGroup( $groupname )) {
	OC_JSON::success(array("data" => array( "groupname" => $groupname )));
}
else{
	OC_JSON::error(array("data" => array( "message" => 'Unable to add group' )));
}
