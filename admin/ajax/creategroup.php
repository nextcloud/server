<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn() || !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$groupname = $_POST["groupname"];

// Does the group exist?
if( in_array( $groupname, OC_Group::getGroups())){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Group already exists" )));
	exit();
}

// Return Success story
if( OC_Group::createGroup( $groupname )){
	echo json_encode( array( "status" => "success", "data" => array( "groupname" => $groupname )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to add group" )));
}

?>
