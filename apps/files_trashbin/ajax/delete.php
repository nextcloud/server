<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$files = $_REQUEST['files'];
$dirlisting = $_REQUEST['dirlisting'];
$list = explode(';', $files);

if (!is_array($list)){
	$list = array($list);
}

$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	if ( $dirlisting=='0') {
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$filename = $file;
		$timestamp = null;
	}

	if(OCA\Files_Trashbin\Trashbin::delete($filename, $timestamp)) {
		$success[$i]['filename'] = $file;
		$success[$i]['timestamp'] = $timestamp;
		$i++;
	} else {
		$error[] = $filename;
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
