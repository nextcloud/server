<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_USER::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

// Get data
if( !isset( $_POST["password"] ) && !isset( $_POST["oldpassword"] )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "You have to enter the old and the new password!" )));
	exit();
}

// Check if the old password is correct
if( !OC_USER::checkPassword( $_SESSION["user_id"], $_POST["oldpassword"] )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Your old password is wrong!" )));
	exit();
}

// Change password
if( OC_USER::setPassword( $_SESSION["user_id"], $_POST["password"] )){
	echo json_encode( array( "status" => "success", "data" => array( "message" => "Password changed" )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to change password" )));
}

?>
