<?php

// only need filesystem apps
$RUNTIME_APPTYPES=array('filesystem');

// Init owncloud
require_once('../../lib/base.php');
require_once('../../lib/template.php');

OC_JSON::checkLoggedIn();

// Load the files
$dir = isset( $_GET['dir'] ) ? $_GET['dir'] : '';
$mimetype = isset($_GET['mimetype']) ? $_GET['mimetype'] : ''; 

// make filelist
$files = array();
foreach( OC_Files::getdirectorycontent( $dir, $mimetype ) as $i ){
	$i["date"] = OC_Util::formatDate($i["mtime"] );
  $i['mimetype_icon'] = $i['type'] == 'dir' ? mimetype_icon('dir'): mimetype_icon($i['mimetype']);
	$files[] = $i;
}

OC_JSON::success(array('data' => $files));

?>
