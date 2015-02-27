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
	
	/**
	 * gets user info
	 *
	 * exposes the quota of an user:
	 * <data>
	 *   <quota>
	 *      <free>1234</free>
	 *      <used>4321</used>
	 *      <total>5555</total>
	 *      <ralative>0.78</ralative>
	 *   </quota>
	 * </data>
	 *
	 * @param array $parameters should contain parameter 'userid' which identifies
	 *                          the user from whom the information will be returned
	 */
	public static function getUser($parameters) {
		$return  = array();
		// Check if they are viewing information on themselves
		if($parameters['userid'] === OC_User::getUser()) {
			// Self lookup
			$storage = OC_Helper::getStorageInfo('/');
			$return['quota'] = array(
				'free' =>  $storage['free'],
				'used' =>  $storage['used'],
				'total' =>  $storage['total'],
				'relative' => $storage['relative'],
				);
		}
		if(OC_User::isAdminUser(OC_User::getUser()) 
			|| OC_Subadmin::isUserAccessible(OC_User::getUser(), $parameters['userid'])) {
			if(OC_User::userExists($parameters['userid'])) {
				// Is an admin/subadmin so can see display name
				$return['displayname'] = OC_User::getDisplayName($parameters['userid']);
			} else {
				return new OC_OCS_Result(null, 101);
			}
		}
		if(count($return)) {
			return new OC_OCS_Result($return);
		} else {
			// No permission to view this user data
			return new OC_OCS_Result(null, 997);
		}
	}

	public static function getCurrentUser() {
		$email=\OC::$server->getConfig()->getUserValue(OC_User::getUser(), 'settings', 'email', '');
		$data  = array(
			'id' => OC_User::getUser(),
			'display-name' => OC_User::getDisplayName(),
			'email' => $email,
		);
		return new OC_OCS_Result($data);
	}
}
