<?php
// Check if we are a user

OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

OCP\JSON::checkAdminUser();

$l=OC_L10N::get('core');

$groupname = $_POST["groupname"];

// Return Success story
// TODO : make changes to the API to allow this.
// setGroupname doesnt exist yet.	
if(OC_Group::setGroupname($groupname)) {
	OC_JSON::success(
		array("data" => array(
			"message" => $l->t('Group name has been changed.'),
			"groupname" => $groupname,
			)
		)
	);
} else {
	OC_JSON::error(array("data" => array( "message" => $l->t("Unable to change group name"))));
}