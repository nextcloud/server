<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn() || !OC_Group::inGroup( OC_User::getUser(), 'admin' )){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

$username = $_POST["username"];
$quota= OC_Helper::computerFileSize($_POST["quota"]);

// Return Success story
OC_Preferences::setValue($username,'files','quota',$quota);
echo json_encode( array( "status" => "success", "data" => array( "username" => $username ,'quota'=>$quota)));

?>
