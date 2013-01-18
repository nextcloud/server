<?php 

if(!OC_User::isLoggedIn()) {
	exit;
}

$timestamp = isset( $_REQUEST['timestamp'] ) ? $_REQUEST['timestamp'] : '';
$filename = isset( $_REQUEST['filename'] ) ? trim($_REQUEST['filename'], '/\\') : '';

OCA_Trash\Trashbin::restore($filename, $timestamp);

//TODO: return useful data after succsessful restore operation and remove restored files from the list view
OCP\JSON::success(array("data" => array('content'=>'foo', 'id' => 'bar')));