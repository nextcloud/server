<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$name = $_POST["groupname"];

// Return Success story
if( OC_Group::deleteGroup( $name )){
	OC_JSON::success(array("data" => array( "groupname" => $name )));
}
else{
	OC_JSON::error(array("data" => array( "message" => "Unable to delete group" )));
}

?>
