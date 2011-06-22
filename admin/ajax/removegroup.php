<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_USER::isLoggedIn() || !OC_GROUP::inGroup( OC_USER::getUser(), 'admin' )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$name = $_POST["groupname"];

// Return Success story
if( OC_GROUP::deleteGroup( $name )){
	echo json_encode( array( "status" => "success", "data" => array( "groupname" => $name )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to delete group" )));
}

?>
