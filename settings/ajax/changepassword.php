<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

$username = isset($_POST["username"]) ? $_POST["username"] : OC_User::getUser();
$password = $_POST["password"];
$oldPassword=isset($_POST["oldpassword"])?$_POST["oldpassword"]:'';

// Check if we are a user
if( !OC_User::isLoggedIn() || (!OC_Group::inGroup( OC_User::getUser(), 'admin' ) && ($username!=OC_User::getUser() || !OC_User::checkPassword($username,$oldPassword)))) {
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

// Return Success story
if( OC_User::setPassword( $username, $password )){
	echo json_encode( array( "status" => "success", "data" => array( "username" => $username )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to change password" )));
}

?>
