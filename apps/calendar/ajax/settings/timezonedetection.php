<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('calendar');
if(array_key_exists('timezonedetection', $_POST) && $_POST['timezonedetection'] == 'on'){
	OCP\Config::setUserValue(OCP\USER::getUser(), 'calendar', 'timezonedetection', 'true');
}else{
	OCP\Config::setUserValue(OCP\USER::getUser(), 'calendar', 'timezonedetection', 'false');
}
OCP\JSON::success();