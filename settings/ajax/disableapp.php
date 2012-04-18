<?php
// Init owncloud
require_once('../../lib/base.php');
OC_JSON::checkAdminUser();
OC_JSON::setContentTypeHeader();

OC_App::disable($_POST['appid']);

OC_JSON::success();
