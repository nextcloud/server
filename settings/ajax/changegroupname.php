<?php

OCP\JSON::callCheck();
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAdminUser();

$l=OC_L10N::get('core');

$groupname = $_POST["groupname"];

// Return Success story
// TODO : make changes to the API to allow this.
// setGroupname doesnt exist yet.	
if(OC_Group::setGroupname($groupname)) {
	OCP\JSON::success(
		array("data" => array(
			"message" => $l->t('Group name has been changed.'),
			"groupname" => $groupname,
			)
		)
	);
} else {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Unable to change group name"))));
}
