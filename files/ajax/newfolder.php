<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

// Get the params
$dir = isset( $_GET['dir'] ) ? stripslashes($_GET['dir']) : '';
$foldername = isset( $_GET['foldername'] ) ? stripslashes($_GET['foldername']) : '';

if(trim($foldername) == '') {
	OC_JSON::error(array("data" => array( "message" => "Empty Foldername" )));
	exit();
}

if(OC_Files::newFile($dir, stripslashes($foldername), 'dir')) {
	OC_JSON::success(array("data" => array()));
	exit();
}

OC_JSON::error(array("data" => array( "message" => "Error when creating the folder" )));
