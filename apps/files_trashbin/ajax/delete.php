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

if (OCA\Files_Trashbin\Trashbin::delete($filename, $timestamp)) {
	OCP\JSON::success(array("data" => array("filename" => $file)));
} else {
	OCP\JSON::error(array("data" => array("message" => "Couldn't delete ".$file. " permanently")));
}

