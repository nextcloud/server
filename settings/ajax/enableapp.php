<?php

// Init owncloud
require_once('../../lib/base.php');
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();
OC_JSON::setContentTypeHeader();

if(OC_App::enable($_POST['appid'])){
	OC_JSON::success();
}else{
	OC_JSON::error();
}
