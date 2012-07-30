<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

if(isset($_POST["firstday"])){
	OCP\Config::setUserValue(OCP\USER::getUser(), 'calendar', 'firstday', $_POST["firstday"]);
	OCP\JSON::success();
}else{
	OCP\JSON::error();
}