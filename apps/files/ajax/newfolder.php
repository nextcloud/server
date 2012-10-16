<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get the params
$dir = isset( $_POST['dir'] ) ? stripslashes($_POST['dir']) : '';
$foldername = isset( $_POST['foldername'] ) ? stripslashes($_POST['foldername']) : '';

if(trim($foldername) == '') {
	OCP\JSON::error(array("data" => array( "message" => "Empty Foldername" )));
	exit();
}
if(strpos($foldername, '/')!==false) {
	OCP\JSON::error(array("data" => array( "message" => "Invalid Foldername" )));
	exit();
}

if(OC_Files::newFile($dir, stripslashes($foldername), 'dir')) {
	if ( $dir != '/') {
		$path = $dir.'/'.$foldername;
	} else {
		$path = '/'.$foldername;
	}
	$id = OC_FileCache::getId($path);
	OCP\JSON::success(array("data" => array('id'=>$id)));
	exit();
}

OCP\JSON::error(array("data" => array( "message" => "Error when creating the folder" )));
