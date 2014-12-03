<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

$files = $_POST['files'];
$dir = '/';
if (isset($_POST['dir'])) {
	$dir = rtrim($_POST['dir'], '/'). '/';
}
$allFiles = false;
if (isset($_POST['allfiles']) and $_POST['allfiles'] === 'true') {
	$allFiles = true;
	$list = array();
	$dirListing = true;
	if ($dir === '' || $dir === '/') {
		$dirListing = false;
	}
	foreach (OCA\Files_Trashbin\Helper::getTrashFiles($dir, \OCP\User::getUser()) as $file) {
		$fileName = $file['name'];
		if (!$dirListing) {
			$fileName .= '.d' . $file['mtime'];
		}
		$list[] = $fileName;
	}
} else {
	$list = json_decode($files);
}

$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	$path = $dir . '/' . $file;
	if ($dir === '/') {
		$file = ltrim($file, '/');
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$path_parts = pathinfo($file);
		$filename = $path_parts['basename'];
		$timestamp = null;
	}

	if ( !OCA\Files_Trashbin\Trashbin::restore($path, $filename, $timestamp) ) {
		$error[] = $filename;
		OC_Log::write('trashbin', 'can\'t restore ' . $filename, OC_Log::ERROR);
	} else {
		$success[$i]['filename'] = $file;
		$success[$i]['timestamp'] = $timestamp;
		$i++;
	}

}

if ( $error ) {
	$filelist = '';
	foreach ( $error as $e ) {
		$filelist .= $e.', ';
	}
	$l = OC::$server->getL10N('files_trashbin');
	$message = $l->t("Couldn't restore %s", array(rtrim($filelist, ', ')));
	OCP\JSON::error(array("data" => array("message" => $message,
										  "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}
