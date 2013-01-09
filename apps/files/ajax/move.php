<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$target = stripslashes(urldecode($_GET["target"]));


if(OC_Files::move($dir, $file, $target, $file)) {
	OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
} else {
	$l=OC_L10N::get('files');
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
}
