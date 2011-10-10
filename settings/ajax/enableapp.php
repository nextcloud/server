<?php

// Init owncloud
require_once('../../lib/base.php');
OC_JSON::checkAdminUser();
OC_JSON::setContentTypeHeader();

OC_App::enable($_POST['appid']);

?>
