<?php
// Check if we are a user

OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

$l = \OC::$server->getL10N('settings');

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$displayName = $_POST["displayName"];

$userstatus = null;
if(OC_User::isAdminUser(OC_User::getUser())) {
	$userstatus = 'admin';
}
if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$userstatus = 'subadmin';
}

if ($username === OC_User::getUser() && OC_User::canUserChangeDisplayName($username)) {
	$userstatus = 'changeOwnDisplayName';
}

if(is_null($userstatus)) {
	OC_JSON::error( array( "data" => array( "message" => $l->t("Authentication error") )));
	exit();
}

// Return Success story
if( OC_User::setDisplayName( $username, $displayName )) {
	OC_JSON::success(array("data" => array( "message" => $l->t('Your full name has been changed.'), "username" => $username, 'displayName' => $displayName )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Unable to change full name"), 'displayName' => OC_User::getDisplayName($username) )));
}
