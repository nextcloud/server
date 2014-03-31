<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$session->close();

// Get data
$dir = stripslashes($_POST["dir"]);
$file = stripslashes($_POST["file"]);
$target = stripslashes(rawurldecode($_POST["target"]));

$l = OC_L10N::get('files');

if(\OC\Files\Filesystem::file_exists($target . '/' . $file)) {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s - File with this name already exists", array($file)) )));
	exit;
}

if ($target != '' || strtolower($file) != 'shared') {
	$targetFile = \OC\Files\Filesystem::normalizePath($target . '/' . $file);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
		OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
	} else {
		OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
}
