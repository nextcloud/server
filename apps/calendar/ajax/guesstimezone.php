<?php
/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
function make_array_out_of_xml ($xml){
	$returnarray = array();
	$xml = (array)$xml ;
	foreach ($xml as $property => $value){
		$value = (array)$value;
		if(!isset($value[0])){
			$returnarray[$property] = make_array_out_of_xml($value);
		}else{
			$returnarray[$property] = trim($value[0]);
		}
	}
	return $returnarray;
}
require_once ("../../../lib/base.php");
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');
$l = new OC_L10N('calendar');
$lat = $_GET['lat'];
$long = $_GET['long'];
$geolocation = file_get_contents('http://ws.geonames.org/timezone?lat=' . $lat . '&lng=' . $long);
//Information are by Geonames (http://www.geonames.org) and licensed under the Creative Commons Attribution 3.0 License
$geoxml = simplexml_load_string($geolocation);
$geoarray = make_array_out_of_xml($geoxml);
if(isset($geoarray['timezone']['timezoneId']) && $geoarray['timezone']['timezoneId'] != ''){
	OC_Preferences::setValue(OC_USER::getUser(), 'calendar', 'timezone', $geoarray['timezone']['timezoneId']);
	$message = array('message'=> $l->t('New Timezone:') . $geoarray['timezone']['timezoneId']);
	OC_JSON::success($message);
}else{
	OC_JSON::error();
}

?>