<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

if(isset($_POST["timeformat"])){
	OCP\Config::setUserValue(OCP\USER::getUser(), 'calendar', 'timeformat', $_POST["timeformat"]);
	OCP\JSON::success();
}else{
	OCP\JSON::error();
}