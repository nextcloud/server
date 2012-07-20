<?php

// Init owncloud
require_once('../../lib/base.php');
OCP\JSON::callCheck();

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$password = $_POST["password"];
$oldPassword=isset($_POST["oldpassword"])?$_POST["oldpassword"]:'';

// Check if we are a user
OC_JSON::checkLoggedIn();
if( (!OC_Group::inGroup( OC_User::getUser(), 'admin' ) && ($username!=OC_User::getUser() || !OC_User::checkPassword($username,$oldPassword)))) {
	OC_JSON::error( array( "data" => array( "message" => "Authentication error" )));
	exit();
}

// Return Success story
if( OC_User::setPassword( $username, $password )){
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to change password" )));
}

?>
