<?php

OCP\JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$data = array();

// make filelist
try {
	$files = \OCA\Files_Trashbin\Helper::getTrashFiles($dir);
} catch (Exception $e) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

$encodedDir = \OCP\Util::encodePath($dir);

$data['permissions'] = 0;
$data['directory'] = $dir;
$data['files'] = \OCA\Files_Trashbin\Helper::formatFileInfos($files);

OCP\JSON::success(array('data' => $data));

