<?php

OCP\JSON::callCheck();
OC_JSON::checkAdminUser();

$groupname = $_POST["groupname"];
$l = OC_L10N::get('core');

// Does the group exist?
if( in_array( $groupname, OC_Group::getGroups())) {
	OC_JSON::error(array("data" => array( "message" => $l->t("Group already exists") )));
	exit();
}

// Return Success story
if( OC_Group::createGroup( $groupname )) {
	OC_JSON::success(array("data" => array( "groupname" => $groupname )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Unable to add group") )));
}
