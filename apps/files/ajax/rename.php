<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_GET["dir"]);
$file = stripslashes($_GET["file"]);
$newname = stripslashes($_GET["newname"]);

// Delete
if( OC_Files::move( $dir, $file, $dir, $newname )) {
	OCP\JSON::success(array("data" => array( "dir" => $dir, "file" => $file, "newname" => $newname )));
}
else{
	OCP\JSON::error(array("data" => array( "message" => "Unable to rename file" )));
}

?>
