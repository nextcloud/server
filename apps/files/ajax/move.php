<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$target = stripslashes(rawurldecode($_GET["target"]));

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
