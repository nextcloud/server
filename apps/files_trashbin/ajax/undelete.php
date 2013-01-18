<?php 

if(!OC_User::isLoggedIn()) {
	exit;
}

$timestamp = isset( $_REQUEST['timestamp'] ) ? $_REQUEST['timestamp'] : '';
$filename = isset( $_REQUEST['filename'] ) ? trim($_REQUEST['filename'], '/\\') : '';

if ( OCA_Trash\Trashbin::restore($filename, $timestamp) ) {
	OCP\JSON::success(array("data" => array('filename'=>$filename, 'timestamp' => $timestamp)));
} else {
	OCP\JSON::error(array("data" => array("message" => "Couldn't restore ".$filename)));	
}
