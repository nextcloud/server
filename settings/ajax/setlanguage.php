<?php

// Init owncloud
require_once('../../lib/base.php');

$l=OC_L10N::get('settings');

OC_JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
if( isset( $_POST['lang'] ) ){
	$languageCodes=OC_L10N::findAvailableLanguages();
	$lang=$_POST['lang'];
	if(array_search($lang,$languageCodes) or $lang=='en'){
		OC_Preferences::setValue( OC_User::getUser(), 'core', 'lang', $lang );
		OC_JSON::success(array("data" => array( "message" => $l->t("Language changed") )));
	}else{
		OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
	}
}else{
	OC_JSON::error(array("data" => array( "message" => $l->t("Invalid request") )));
}

?>
