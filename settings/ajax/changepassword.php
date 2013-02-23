<?php

// Check if we are a user
OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

// Manually load apps to ensure hooks work correctly (workaround for issue 1503)
OC_APP::loadApps();

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$password = isset($_POST["password"]) ? $_POST["password"] : null;
$oldPassword=isset($_POST["oldpassword"])?$_POST["oldpassword"]:'';

$userstatus = null;
if(OC_User::isAdminUser(OC_User::getUser())) {
	$userstatus = 'admin';
}
if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$userstatus = 'subadmin';
}
if(OC_User::getUser() === $username && OC_User::checkPassword($username, $oldPassword)) {
	$userstatus = 'user';
}

if(is_null($userstatus)) {
	OC_JSON::error( array( "data" => array( "message" => "Authentication error" )));
	exit();
}

// Return Success story
if(!is_null($password) && OC_User::setPassword( $username, $password )) {
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to change password" )));
}
