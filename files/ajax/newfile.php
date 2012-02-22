<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get the params
$dir = isset( $_GET['dir'] ) ? stripslashes($_GET['dir']) : '';
$filename = isset( $_GET['filename'] ) ? stripslashes($_GET['filename']) : '';
$content = isset( $_GET['content'] ) ? $_GET['content'] : '';

if($filename == '') {
	OC_JSON::error(array("data" => array( "message" => "Empty Filename" )));
	exit();
}

if(OC_Files::newFile($dir, $filename, 'file')) {
	if($content){
		OC_Filesystem::file_put_contents($dir.'/'.$filename,$content);
	}
	OC_JSON::success(array("data" => array('content'=>$content)));
	exit();
}


OC_JSON::error(array("data" => array( "message" => "Error when creating the file" )));
