<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

// Get data
$dir = isset($_POST['dir']) ? $_POST['dir'] : '';
$file = isset($_POST['file']) ? $_POST['file'] : '';
$target = isset($_POST['target']) ? rawurldecode($_POST['target']) : '';

$l = \OC::$server->getL10N('files');

if(\OC\Files\Filesystem::file_exists($target . '/' . $file)) {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s - File with this name already exists", array($file)) )));
	exit;
}

if ($target != '' || strtolower($file) != 'shared') {
	$targetFile = \OC\Files\Filesystem::normalizePath($target . '/' . $file);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	try {
		if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
			OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
		} else {
			OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
		}
	} catch (\OCP\Files\NotPermittedException $e) {
		OCP\JSON::error(array("data" => array( "message" => $l->t("Permission denied") )));
	} catch (\Exception $e) {
		OCP\JSON::error(array("data" => array( "message" => $e->getMessage())));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
}
