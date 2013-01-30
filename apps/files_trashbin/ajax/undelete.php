<?php 

if(!OCP\User::isLoggedIn()) {
	exit;
}

$files = $_REQUEST['files'];
$dirlisting = $_REQUEST['dirlisting'];
$list = explode(';', $files);

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
	
	if ( !OCA_Trash\Trashbin::restore($file, $filename, $timestamp) ) {
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
	OCP\JSON::error(array("data" => array("message" => "Couldn't restore ".rtrim($filelist,', '), "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}

