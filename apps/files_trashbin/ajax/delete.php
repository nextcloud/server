<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$file = $_REQUEST['file'];

$path_parts = pathinfo($file);
if ($path_parts['dirname'] == '.') {
	$delimiter = strrpos($file, '.d');
	$filename = substr($file, 0, $delimiter);
	$timestamp =  substr($file, $delimiter+2);
} else {
	$filename = $file;
	$timestamp = null;
}

OCA\Files_Trashbin\Trashbin::delete($filename, $timestamp);

if (!OCA\Files_Trashbin\Trashbin::file_exists($filename)) {
	OCP\JSON::success(array("data" => array("filename" => $file)));
} else {
	$l = OC_L10N::get('files_trashbin');
	OCP\JSON::error(array("data" => array("message" => $l->t("Couldn't delete %s permanently", array($file)))));
}

