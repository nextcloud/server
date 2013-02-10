<?php
/**
* ownCloud
*
* @author Frank Karlitschek
* @author Tom Needham
* @copyright 2012 Frank Karlitschek frank@owncloud.org
* @copyright 2012 Tom Needham tom@owncloud.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

class OC_OCS_Cloud {

	public static function getCapabilities($parameters) {
		$result = array();
		list($major, $minor, $micro) = OC_Util::getVersion();
		$result['version'] = array(
			'major' => $major,
			'minor' => $minor,
			'micro' => $micro,
			'string' => OC_Util::getVersionString(),
			'edition' => OC_Util::getEditionString(),
			);
			
			$result['capabilities'] = array(
				'core' => array(
					'pollinterval' => OC_Config::getValue('pollinterval', 60),
					),
				);
		return new OC_OCS_Result($result);
	}

	public static function getUserPublickey($parameters) {

		if(OC_User::userExists($parameters['user'])) {
			// calculate the disc space
			// TODO
			return new OC_OCS_Result(array());
		} else {
			return new OC_OCS_Result(null, 300);
		}
	}

	public static function getUserPrivatekey($parameters) {
		$user = OC_User::getUser();
		if(OC_User::isAdminUser($user) or ($user==$parameters['user'])) {

			if(OC_User::userExists($user)) {
				// calculate the disc space
				$txt = 'this is the private key of '.$parameters['user'];
				echo($txt);
			} else {
				return new OC_OCS_Result(null, 300, 'User does not exist');
			}
		} else {
			return new OC_OCS_Result('null', 300, 'You don´t have permission to access this ressource.');
		}
	}
}
