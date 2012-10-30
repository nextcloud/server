<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$target = stripslashes(rawurldecode($_GET["target"]));

if ($dir != '' || $file != 'Shared') {
	$targetFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	$sourceFile = \OC\Files\Filesystem::normalizePath($target . '/' . $file);
	if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
		OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
	} else {
		OCP\JSON::error(array("data" => array( "message" => "Could not move $file" )));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => "Could not move $file" )));
}
