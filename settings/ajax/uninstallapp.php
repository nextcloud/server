<?php
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OC_JSON::error();
	exit;
}

$appId = $_POST['appid'];
$appId = OC_App::cleanAppId($appId);

$result = OC_App::removeApp($appId);
if($result !== false) {
	// FIXME: Clear the cache - move that into some sane helper method
	\OC::$server->getMemCacheFactory()->create('settings')->remove('listApps-0');
	\OC::$server->getMemCacheFactory()->create('settings')->remove('listApps-1');
	OC_JSON::success(array('data' => array('appid' => $appId)));
} else {
	$l = \OC::$server->getL10N('settings');
	OC_JSON::error(array("data" => array( "message" => $l->t("Couldn't remove app.") )));
}
