<?php
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();
OC_JSON::setContentTypeHeader();

OC_App::disable($_POST['appid']);

OC_JSON::success();
