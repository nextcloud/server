<?php

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l=OC_L10N::get('core');

// Get data
if( isset( $_POST['email'] ) && filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL) ){
	$email=trim($_POST['email']);
	OC_Preferences::setValue(OC_User::getUser(),'settings','email',$email);
	OC_JSON::success(array("data" => array( "message" => $l->t("Email saved") )));
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid email") )));
}

?>