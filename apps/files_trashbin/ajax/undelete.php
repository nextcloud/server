<?php 

if(!OC_User::isLoggedIn()) {
	exit;
}

$files = $_REQUEST['files'];
$list = explode(';', $files);

$error = array();

$i = 0;
foreach ($list as $file) {
	$delimiter = strrpos($file, '.d');
	$filename = substr($file, 0, $delimiter);
	$timestamp =  substr($file, $delimiter+2);
	
	if ( !OCA_Trash\Trashbin::restore($filename, $timestamp) ) {
		$error[] = $filename;
	} else {
		$success[$i]['filename'] = $filename;
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

