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
$dir = $_GET["dir"];
$file = $_GET["file"];

// Delete
if( OC_FILES::delete( $dir, $file )){
	echo json_encode( array( "status" => "success", "data" => array( "dir" => $dir, "file" => $file )));
}
else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Unable to delete file" )));
}

?>
