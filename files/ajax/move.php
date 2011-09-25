<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get data
$dir = $_GET["dir"];
$file = $_GET["file"];
$target = $_GET["target"];


if(OC_Files::move($dir,$file,$target,$file)){
	OC_JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
}else{
	OC_JSON::error(array("data" => array( "message" => "Could move $file" )));
}

?>
