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
if( isset( $_POST['lang'] ) ){
	$lang=$_POST['lang'];
	OC_PREFERENCES::setValue( $_SESSION['user_id'], 'core', 'lang', $lang );
	echo json_encode( array( "status" => "success", "data" => array( "message" => "Language changed" )));
}else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Invalid request" )));
}

?>
