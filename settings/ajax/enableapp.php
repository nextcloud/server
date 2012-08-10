<?php

// Init owncloud
require_once('../../lib/base.php');
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();
OC_JSON::setContentTypeHeader();

$appid = OC_App::enable($_POST['appid']);
if($appid !== false) {
	OC_JSON::success(array('data' => array('appid' => $appid)));
} else {
	OC_JSON::error();
}
