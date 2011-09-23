<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get data
$dir = $_GET["dir"];
$file = $_GET["file"];
$newname = $_GET["newname"];

// Delete
if( OC_Files::move( $dir, $file, $dir, $newname )) {
	OC_JSON::success(array("data" => array( "dir" => $dir, "file" => $file, "newname" => $newname )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to rename file" )));
}

?>
