<?php

// Init owncloud

OC_JSON::checkAdminUser();
OC_JSON::setContentTypeHeader();

if(OC_App::enable($_POST['appid'])){
	OC_JSON::success();
}else{
	OC_JSON::error();
}
