<?php
/**
 * Copyright (c) 2012, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$username = isset($_POST["username"])?$_POST["username"]:'';

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
if($username){
	OC_Preferences::setValue($username,'files','quota',$quota);
}else{//set the default quota when no username is specified
	if($quota=='default'){//'default' as default quota makes no sense
		$quota='none';
	}
	OC_Appconfig::setValue('files','default_quota',$quota);
}
OC_JSON::success(array("data" => array( "username" => $username ,'quota'=>$quota)));

?>
