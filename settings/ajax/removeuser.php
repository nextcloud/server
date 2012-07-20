<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$username = $_POST["username"];

// Return Success story
if( OC_User::deleteUser( $username )){
	OC_JSON::success(array("data" => array( "username" => $username )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to delete user" )));
}

?>
