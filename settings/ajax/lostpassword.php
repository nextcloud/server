<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();

$l=OC_L10N::get('core');

// Get data
if( isset( $_POST['email'] ) ){
	$email=trim($_POST['email']);
	OC_Preferences::setValue(OC_User::getUser(),'settings','email',$email);
	OC_JSON::success(array("data" => array( "message" => $l->t("email Changed") )));
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
}

?>
