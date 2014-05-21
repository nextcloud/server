<?php
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OC_JSON::error();
	exit;
}

$appId = $_POST['appid'];
$appId = OC_App::cleanAppId($appId);

OC_App::disable($appId);
OC_JSON::success();