<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_POST["dir"]);
$file = stripslashes($_POST["file"]);
$target = stripslashes(rawurldecode($_POST["target"]));

$l=OC_L10N::get('files');

if(OC_Filesystem::file_exists($target . '/' . $file)) {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s - File with this name already exists", array($file)) )));
	exit;
}

if(OC_Files::move($dir, $file, $target, $file)) {
	OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
} else {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
}
