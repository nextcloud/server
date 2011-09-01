<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

// Get data
$dir = $_GET["dir"];
$file = $_GET["file"];
$target = $_GET["target"];


if(OC_Files::move($dir,$file,$target,$file)){
	echo json_encode( array( "status" => 'success', "data" => array( "dir" => $dir, "files" => $file )));
}else{
	echo json_encode( array( "status" => 'error', "data" => array( "message" => "Could move $file" )));
}

?>