<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$appid = OC_App::enable(OC_App::cleanAppId($_POST['appid']));
if($appid !== false) {
	OC_JSON::success(array('data' => array('appid' => $appid)));
} else {
	$l = OC_L10N::get('settings');	
	OC_JSON::error(array("data" => array( "message" => $l->t("Could not enable app. ") )));
}
