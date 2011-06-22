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

$groupname = $_POST["groupname"];

// Does the group exist?
if( in_array( $groupname, OC_GROUP::getGroups())){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Group already exists" )));
	exit();
}

// Return Success story
if( OC_GROUP::createGroup( $groupname )){
	echo json_encode( array( "status" => "success", "data" => array( "groupname" => $groupname )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to add group" )));
}

?>
