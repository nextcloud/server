<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$files = isset($_GET["file"]) ? stripslashes($_GET["file"]) : stripslashes($_GET["files"]);

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
	OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $files )));
} else {
	OCP\JSON::error(array("data" => array( "message" => "Could not delete:\n" . $filesWithError )));
}

?>
