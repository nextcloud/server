<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';

// make filelist
$files = array();
foreach( OC_Files::getdirectorycontent( $dir ) as $i ){
	$i["date"] = OC_Util::formatDate($i["mtime"] );
	$files[] = $i;
}

OC_JSON::success(array('data' => $files));

?>
