<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

$folder = isset($_POST['dir']) ? $_POST['dir'] : '/';

// "empty trash" command
if (isset($_POST['allfiles']) and $_POST['allfiles'] === 'true'){
	$deleteAll = true;
	if ($folder === '/' || $folder === '') {
		OCA\Files_Trashbin\Trashbin::deleteAll();
		$list = array();
	} else {
		$list[] = $folder;
		$folder = dirname($folder);
	}
}
else {
	$deleteAll = false;
	$files = $_POST['files'];
	$list = json_decode($files);
}

$folder = rtrim($folder, '/') . '/';
$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	if ($folder === '/') {
		$file = ltrim($file, '/');
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$filename = $folder . '/' . $file;
		$timestamp = null;
	}

	OCA\Files_Trashbin\Trashbin::delete($filename, \OCP\User::getUser(), $timestamp);
	if (OCA\Files_Trashbin\Trashbin::file_exists($filename, $timestamp)) {
		$error[] = $filename;
		OC_Log::write('trashbin','can\'t delete ' . $filename . ' permanently.', OC_Log::ERROR);
	}
	// only list deleted files if not deleting everything
	else if (!$deleteAll) {
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
	$l = \OC::$server->getL10N('files_trashbin');
	$message = $l->t("Couldn't delete %s permanently", array(rtrim($filelist, ', ')));
	OCP\JSON::error(array("data" => array("message" => $message,
			                               "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}
