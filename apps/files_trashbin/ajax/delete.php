<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// "empty trash" command
if (isset($_POST['allfiles']) and $_POST['allfiles'] === 'true'){
	$deleteAll = true;
	$folder = isset($_POST['dir']) ? $_POST['dir'] : '/';
	if ($folder === '/' || $folder === '') {
		OCA\Files_Trashbin\Trashbin::deleteAll();
		$list = array();
	} else {
		$dirname = dirname($folder);
		if ( $dirname !== '/' && $dirname !== '.' ) {
			$dirlisting = '1';
		} else {
			$dirlisting = '0';
		}
		$list[] = $folder;
	}
}
else {
	$deleteAll = false;
	$files = $_POST['files'];
	$dirlisting = $_POST['dirlisting'];
	$list = json_decode($files);
}
$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	if ( $dirlisting === '0') {
		$file = ltrim($file, '/');
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$filename = $file;
		$timestamp = null;
	}

	OCA\Files_Trashbin\Trashbin::delete($filename, $timestamp);
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
	$l = OC_L10N::get('files_trashbin');
	$message = $l->t("Couldn't delete %s permanently", array(rtrim($filelist, ', ')));
	OCP\JSON::error(array("data" => array("message" => $message,
			                               "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}
