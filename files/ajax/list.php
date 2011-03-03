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

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';

$files = array();
foreach( OC_FILES::getdirectorycontent( $dir ) as $i ){
	$i["date"] = date( $CONFIG_DATEFORMAT, $i["mtime"] );
	$files[] = $i;
}

// Make breadcrumb
$breadcrumb = array();
$pathtohere = "/";
foreach( explode( "/", $dir ) as $i ){
	if( $i != "" ){
		$pathtohere .= "$i/";
		$breadcrumb[] = array( "dir" => $pathtohere, "name" => $i );
	}
}

echo json_encode( array( "status" => "success", "data" => array( "files" => $files, "breadcrumb" => $breadcrumb )));

?>
