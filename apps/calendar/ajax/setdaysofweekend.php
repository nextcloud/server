<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
$weekenddays = array("Monday"=>"false", "Tuesday"=>"false", "Wednesday"=>"false", "Thursday"=>"false", "Friday"=>"false", "Saturday"=>"false", "Sunday"=>"false");
for($i = 0;$i < count($_POST["weekend"]); $i++){
	switch ($_POST["weekend"][$i]){
		case "Monday":
		case "Tuesday":
		case "Wednesday":
		case "Thursday":
		case "Friday":
		case "Saturday":
		case "Sunday":
			break;
		default:
			OC_JSON::error();
			exit;
	}
	$weekenddays[$_POST["weekend"][$i]] = "true";	
}
$setValue = json_encode($weekenddays);
OC_Preferences::setValue(OC_User::getUser(), 'calendar', 'weekend', $setValue);
OC_JSON::success();
?> 
