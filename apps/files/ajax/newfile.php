<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get the params
$dir = isset( $_POST['dir'] ) ? stripslashes($_POST['dir']) : '';
$filename = isset( $_POST['filename'] ) ? stripslashes($_POST['filename']) : '';
$content = isset( $_POST['content'] ) ? $_POST['content'] : '';
$source = isset( $_POST['source'] ) ? stripslashes($_POST['source']) : '';

if($filename == '') {
	OCP\JSON::error(array("data" => array( "message" => "Empty Filename" )));
	exit();
}
if(strpos($filename,'/')!==false){
	OCP\JSON::error(array("data" => array( "message" => "Invalid Filename" )));
	exit();
}

if($source){
	if(substr($source,0,8)!='https://' and substr($source,0,7)!='http://'){
		OCP\JSON::error(array("data" => array( "message" => "Not a valid source" )));
		exit();
	}
	$sourceStream=fopen($source,'rb');
	$target=$dir.'/'.$filename;
	$result=OC_Filesystem::file_put_contents($target,$sourceStream);
	if($result){
		$mime=OC_Filesystem::getMimetype($target);
		OCP\JSON::success(array("data" => array('mime'=>$mime)));
		exit();
	}else{
		OCP\JSON::error(array("data" => array( "message" => "Error while downloading ".$source. ' to '.$target )));
		exit();
	}
}else{
	if($content){
		if(OC_Filesystem::file_put_contents($dir.'/'.$filename,$content)){
			OCP\JSON::success(array("data" => array('content'=>$content)));
			exit();
		}
	}elseif(OC_Files::newFile($dir, $filename, 'file')){
		OCP\JSON::success(array("data" => array('content'=>$content)));
		exit();
	}
}


OCP\JSON::error(array("data" => array( "message" => "Error when creating the file" )));
