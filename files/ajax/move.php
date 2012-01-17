<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$target = stripslashes($_GET["target"]);


if(OC_Files::move($dir,$file,$target,$file)){
	OC_JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
}else{
	OC_JSON::error(array("data" => array( "message" => "Could not move $file" )));
}

?>
