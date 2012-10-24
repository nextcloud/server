<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$newname = stripslashes($_GET["newname"]);

if (OC_User::isLoggedIn() && ($dir != '' || $file != 'Shared')) {
	$targetFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $newname);
	if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
		OCP\JSON::success(array("data" => array( "dir" => $dir, "file" => $file, "newname" => $newname )));
	} else {
		OCP\JSON::error(array("data" => array( "message" => "Unable to rename file" )));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => "Unable to rename file" )));
}
