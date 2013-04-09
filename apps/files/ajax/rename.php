<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$newname = stripslashes($_GET["newname"]);

$l = OC_L10N::get('files');

if ( $newname !== '.' and ($dir !== '/' || $file !== 'Shared') and ($dir !== '/' || $newname !== 'Shared') ) {
	$targetFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $newname);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
		OCP\JSON::success(array("data" => array( "dir" => $dir, "file" => $file, "newname" => $newname )));
	} else {
		OCP\JSON::error(array("data" => array( "message" => $l->t("Unable to rename file") )));
	}
} elseif( $dir === '/' and $newname === 'Shared' ) {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Invalid folder name. Usage of 'Shared' is reserved by Owncloud") )));
} else {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Unable to rename file") )));
}
