<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_USER::isLoggedIn() || !OC_GROUP::inGroup( $_SESSION['user_id'], 'admin' )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$groups = array();
$username = $_POST["username"];
$password = $_POST["password"];
$groups = $_POST["groups"];

$success = true;
if( $password ){
	$success = $success && OC_USER::setPassword( $username, $password );
}

// update groups (delete old ones, add new ones)
foreach( OC_GROUP::getUserGroups( $username ) as $i ){
	OC_GROUP::removeFromGroup( $username, $i );
}
foreach( $groups as $i ){
	OC_GROUP::addToGroup( $username, $i );
}

// Return Success story
if( $success ){
	echo json_encode( array( "status" => "success", "data" => array( "username" => $username, "groups" => implode( ", ", $groups ))));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Edit user" )));
}

?>
