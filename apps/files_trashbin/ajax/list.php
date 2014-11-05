<?php

OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$sortAttribute = isset( $_GET['sort'] ) ? $_GET['sort'] : 'name';
$sortDirection = isset( $_GET['sortdirection'] ) ? ($_GET['sortdirection'] === 'desc') : false;
$data = array();

// make filelist
try {
	$files = \OCA\Files_Trashbin\Helper::getTrashFiles($dir, \OCP\User::getUser(), $sortAttribute, $sortDirection);
} catch (Exception $e) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

$encodedDir = \OCP\Util::encodePath($dir);

$data['permissions'] = 0;
$data['directory'] = $dir;
$data['files'] = \OCA\Files_Trashbin\Helper::formatFileInfos($files);

OCP\JSON::success(array('data' => $data));

