<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class OC_Geo{
	/*
	 * @brief returns the closest timezone to coordinates
	 * @param (string) $latitude - Latitude
	 * @param (string) $longitude - Longitude
	 * @return (string) $timezone - closest timezone
	 */
	public static function timezone($latitude, $longitude){	
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
}