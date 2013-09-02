<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

try {
	OC_App::enable(OC_App::cleanAppId($_POST['appid']));
	OC_JSON::success();
} catch (Exception $e) {
	OC_Log::write('core', $e->getMessage(), OC_Log::ERROR);
	OC_JSON::error(array("data" => array("message" => $e->getMessage()) ));
}
