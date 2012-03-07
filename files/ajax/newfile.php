<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get the params
$dir = isset( $_POST['dir'] ) ? stripslashes($_POST['dir']) : '';
$filename = isset( $_POST['filename'] ) ? stripslashes($_POST['filename']) : '';
$content = isset( $_POST['content'] ) ? $_POST['content'] : '';
$source = isset( $_POST['source'] ) ? stripslashes($_POST['source']) : '';

if($filename == '') {
	OC_JSON::error(array("data" => array( "message" => "Empty Filename" )));
	exit();
}

if($source){
	if(substr($source,0,8)!='https://' and substr($source,0,7)!='http://'){
		OC_JSON::error(array("data" => array( "message" => "Not a valid source" )));
		exit();
	}
	$sourceStream=fopen($source,'rb');
	$target=$dir.'/'.$filename;
	$result=OC_Filesystem::file_put_contents($target,$sourceStream);
	if($result){
		$mime=OC_Filesystem::getMimetype($target);
		OC_JSON::success(array("data" => array('mime'=>$mime)));
		exit();
	}else{
		OC_JSON::error(array("data" => array( "message" => "Error while downloading ".$source. ' to '.$target )));
		exit();
	}
}


if(OC_Files::newFile($dir, $filename, 'file')) {
	if($content){
		OC_Filesystem::file_put_contents($dir.'/'.$filename,$content);
	}
	OC_JSON::success(array("data" => array('content'=>$content)));
	exit();
}


OC_JSON::error(array("data" => array( "message" => "Error when creating the file" )));
