<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$appid = $_POST['appid'];
$appid = OC_App::cleanAppId($appid);

$result = OC_Installer::updateApp($appid);
if($result !== false) {
	OC_JSON::success(array('data' => array('appid' => $appid)));
} else {
	$l = OC_L10N::get('settings');	
	OC_JSON::error(array("data" => array( "message" => $l->t("Couldn't update app.") )));
}