<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get data
$dir = $_GET["dir"];
$files = isset($_GET["file"]) ? $_GET["file"] : $_GET["files"];

$files = explode(';', $files);
$filesWithError = '';
$success = true;
//Now delete
foreach($files as $file) {
    if( !OC_Files::delete( $dir, $file )){
		$filesWithError .= $file . "\n";
		$success = false;
	}
}

if($success) {
	OC_JSON::success(array("data" => array( "dir" => $dir, "files" => $files )));
} else {
	OC_JSON::error(array("data" => array( "message" => "Could not delete:\n" . $filesWithError )));
}

?>
