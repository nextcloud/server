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

$groups = array();
if( isset( $_POST["groups"] )){
	$groups = $_POST["groups"];
}
$username = $_POST["username"];
$password = $_POST["password"];

// Does the group exist?
if( in_array( $username, OC_User::getUsers())){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "User already exists" )));
	exit();
}

// Return Success story
if( OC_User::createUser( $username, $password )){
	foreach( $groups as $i ){
		if(!OC_Group::groupExists($i)){
			OC_Group::createGroup($i);
		}
		OC_Group::addToGroup( $username, $i );
	}
	echo json_encode( array( "status" => "success", "data" => array( "username" => $username, "groups" => implode( ", ", OC_Group::getUserGroups( $username )))));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to add user" )));
}

?>
