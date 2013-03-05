<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$files = $_POST['files'];
$dirlisting = $_POST['dirlisting'];
$list = json_decode($files);

$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	if ( $dirlisting=='0') {
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$path_parts = pathinfo($file);
		$filename = $path_parts['basename'];
		$timestamp = null;
	}

	if ( !OCA\Files_Trashbin\Trashbin::restore($file, $filename, $timestamp) ) {
		$error[] = $filename;
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
	$l = OC_L10N::get('files_trashbin');
	$message = $l->t("Couldn't restore %s", array(rtrim($filelist, ', ')));
	OCP\JSON::error(array("data" => array("message" => $message,
										  "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}
