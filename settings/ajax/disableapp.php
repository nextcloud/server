<?php
// Init owncloud
require_once '../../lib/base.php';
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();
OC_JSON::setContentTypeHeader();

OC_App::disable(OC_App::cleanAppId($_POST['appid']));

OC_JSON::success();
