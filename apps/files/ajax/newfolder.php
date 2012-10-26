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

if(\OC\Files\Filesystem::mkdir($dir . '/' . stripslashes($foldername))) {
	if ( $dir != '/') {
		$path = $dir.'/'.$foldername;
	} else {
		$path = '/'.$foldername;
	}
	$meta = \OC\Files\Filesystem::getFileInfo($path);
	$id = $meta['fileid'];
	OCP\JSON::success(array("data" => array('id'=>$id)));
	exit();
}

OCP\JSON::error(array("data" => array( "message" => "Error when creating the folder" )));
