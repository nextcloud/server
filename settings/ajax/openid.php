<?php

// Init owncloud
require_once('../../lib/base.php');

$l=OC_L10N::get('settings');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('user_openid');

// Get data
if( isset( $_POST['identity'] ) ){
	$identity=$_POST['identity'];
	OC_Preferences::setValue(OC_User::getUser(),'user_openid','identity',$identity);
	OC_JSON::success(array("data" => array( "message" => $l->t("OpenID Changed") )));
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
}

?>
