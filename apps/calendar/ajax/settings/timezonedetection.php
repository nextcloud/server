<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');
if(array_key_exists('timezonedetection', $_POST)){
	if($_POST['timezonedetection'] == 'on'){
		OC_Preferences::setValue(OCP\USER::getUser(), 'calendar', 'timezonedetection', 'true');
	}else{
		OC_Preferences::setValue(OCP\USER::getUser(), 'calendar', 'timezonedetection', 'false');
	}
	OC_JSON::success();
}else{
	OC_JSON::error();
}