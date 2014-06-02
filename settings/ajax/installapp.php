<?php
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OC_JSON::error();
	exit;
}

$appId = $_POST['appid'];
$appId = OC_App::cleanAppId($appId);

$result = OC_App::installApp($appId);
if($result !== false) {
	OC_JSON::success(array('data' => array('appid' => $appId)));
} else {
	$l = OC_L10N::get('settings');
	OC_JSON::error(array("data" => array( "message" => $l->t("Couldn't remove app.") )));
}
