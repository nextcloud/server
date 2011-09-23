<?php

// Init owncloud
require_once('../../lib/base.php');

$l=new OC_L10N('settings');

OC_JSON::checkLoggedIn();

// Get data
if( isset( $_POST['lang'] ) ){
	$lang=$_POST['lang'];
	OC_Preferences::setValue( OC_User::getUser(), 'core', 'lang', $lang );
	OC_JSON::success(array("data" => array( "message" => $l->t("Language changed") )));
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
}

?>
