<?php

OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('settings');

// Get data
if( isset( $_POST['email'] ) && OC_Mail::validateAddress($_POST['email']) ) {
	$email=trim($_POST['email']);
	OC_Preferences::setValue(OC_User::getUser(), 'settings', 'email', $email);
	OC_JSON::success(array("data" => array( "message" => $l->t("Email saved") )));
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid email") )));
}
