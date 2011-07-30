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
$files = isset($_GET["file"]) ? $_GET["file"] : $_GET["files"];

$files = explode(';', $files);
$filesWithError = '';
$status = 'success';
//Now delete
foreach($files as $file) {
    if( !OC_Files::delete( $dir, $file )){
		$filesWithError .= $file . "\n";
		$status = 'error';
	}
}

if($status == 'success') {
	echo json_encode( array( "status" => $status, "data" => array( "dir" => $dir, "files" => $files )));
} else {
	echo json_encode( array( "status" => $status, "data" => array( "message" => "Could not delete:\n" . $filesWithError )));
}

?>
