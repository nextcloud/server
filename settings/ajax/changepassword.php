<?php

// Check if we are a user
OCP\JSON::callCheck();
OC_JSON::checkLoggedIn();

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$password = $_POST["password"];
$oldPassword=isset($_POST["oldpassword"])?$_POST["oldpassword"]:'';

$userstatus = null;
if(OC_User::isAdminUser(OC_User::getUser())) {
	$userstatus = 'admin';
}
if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$userstatus = 'subadmin';
}
if(OC_User::getUser() === $username) {
	if (OC_User::checkPassword($username, $oldPassword)) {
		$userstatus = 'user';
	}  else {
		if (!OC_Util::isUserVerified()) {
			$userstatus = null;
		}
	}
}

if(is_null($userstatus)) {
	OC_JSON::error( array( "data" => array( "message" => "Authentication error" )));
	exit();
}

// Return Success story
if( OC_User::setPassword( $username, $password )) {
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to change password" )));
}
