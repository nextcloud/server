<?php
/**
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Scrutinizer Auto-Fixer <auto-fixer@scrutinizer-ci.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
class OC_Geo{
	/**
	 * returns the closest timezone to coordinates
	 * @param float $latitude
	 * @param float $longitude
	 * @return mixed Closest timezone
	 */
	public static function timezone($latitude, $longitude) {
		$alltimezones = DateTimeZone::listIdentifiers();
		$variances = array();
		//calculate for all timezones the system know
		foreach($alltimezones as $timezone) {
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
