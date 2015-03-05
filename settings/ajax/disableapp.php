<?php
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OC_JSON::error();
	exit;
}

$appId = $_POST['appid'];
$appId = OC_App::cleanAppId($appId);

// FIXME: Clear the cache - move that into some sane helper method
\OC::$server->getMemCacheFactory()->create('settings')->clear('listApps-');

OC_App::disable($appId);
OC_JSON::success();
