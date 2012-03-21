<?php
/**
 * Copyright (c) 2011, 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function detect_timezone($latitude, $longitude){
	$alltimezones = DateTimeZone::listIdentifiers();
	$variances = array();
	//calculate for all timezones the system know
	foreach($alltimezones as $timezone){
		$datetimezoneobj = new DateTimeZone($timezone);
		$locationinformations = $datetimezoneobj->getLocation();
		$latitudeoftimezone = $locationinformations['latitude'];
		$longitudeoftimezone = $locationinformations['longitude'];
		$variances[abs($latitudeoftimezone - $latitude) + abs($longitudeoftimezone - $longitude)] = $timezone;
	}
	//sort array and return the timezone with the smallest difference
	ksort($variances);
	reset($variances);
	return current($variances);
}

require_once ("../../../lib/base.php");

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$l = new OC_L10N('calendar');

$lat = $_GET['lat'];
$lng = $_GET['long'];

$timezone =  detect_timezone($lat, $lng);

if($timezone == OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'timezone')){
	OC_JSON::success();
	exit;
}
OC_Preferences::setValue(OC_USER::getUser(), 'calendar', 'timezone', $timezone);
$message = array('message'=> $l->t('New Timezone:') . $timezone);
OC_JSON::success($message);
?>