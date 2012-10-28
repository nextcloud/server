<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$target = stripslashes(urldecode($_GET["target"]));


if(OC_Filesystem::file_exists($target . '/' . $file)){
	OCP\JSON::error(array("data" => array( "message" => "Could not move $file - File with this name already exists" )));
	exit;
}

if(OC_Files::move($dir, $file, $target, $file)) {
	OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
} else {
	OCP\JSON::error(array("data" => array( "message" => "Could not move $file" )));
}
