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

// Get the params
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$foldername = isset( $_GET['foldername'] ) ? $_GET['foldername'] : '';

if($foldername == '') {
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Empty Foldername" )));
	exit();
}
error_log('try to create ' . $foldername . ' in ' . $dir);
if(OC_Files::newFile($dir, $foldername, 'dir')) {
	echo json_encode( array( "status" => "success", "data" => array()));
	exit();
}

echo json_encode( array( "status" => "error", "data" => array( "message" => "Error when creating the folder" )));