<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();

$username = $_POST["username"];

//make sure the quota is in the expected format
$quota= OC_Helper::computerFileSize($_POST["quota"]);
$quota=OC_Helper::humanFileSize($quota);

// Return Success story
OC_Preferences::setValue($username,'files','quota',$quota);
OC_JSON::success(array("data" => array( "username" => $username ,'quota'=>$quota)));

?>
