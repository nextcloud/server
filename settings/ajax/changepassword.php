<?php

// Init owncloud
require_once '../../lib/base.php';
OCP\JSON::callCheck();

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$password = $_POST["password"];
$oldPassword=isset($_POST["oldpassword"])?$_POST["oldpassword"]:'';

// Check if we are a user
OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();

$userstatus = null;
if(OC_Group::inGroup(OC_User::getUser(), 'admin')) {
	$userstatus = 'admin';
}
if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $username)) {
	$userstatus = 'subadmin';
}
if(OC_User::getUser() == $username && OC_User::checkPassword($username, $oldPassword)) {
	$userstatus = 'user';
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
