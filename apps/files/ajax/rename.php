<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$newname = stripslashes($_GET["newname"]);

$l = OC_L10N::get('files');

if ( $newname !== '.' and ($dir != '' || $file != 'Shared') and $newname !== '.') {
	$targetFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $newname);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
		OCP\JSON::success(array("data" => array( "dir" => $dir, "file" => $file, "newname" => $newname )));
	} else {
		OCP\JSON::error(array("data" => array( "message" => $l->t("Unable to rename file") )));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => $l->t("Unable to rename file") )));
}
