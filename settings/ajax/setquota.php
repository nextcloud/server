<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();

$username = $_POST["username"];

//make sure the quota is in the expected format
$quota=$_POST["quota"];
if($quota!='none' and $quota!='default'){
	$quota= OC_Helper::computerFileSize($quota);
	if($quota==0){
		$quota='default';
	}else{
		$quota=OC_Helper::humanFileSize($quota);
	}
}

// Return Success story
OC_Preferences::setValue($username,'files','quota',$quota);
OC_JSON::success(array("data" => array( "username" => $username ,'quota'=>$quota)));

?>
