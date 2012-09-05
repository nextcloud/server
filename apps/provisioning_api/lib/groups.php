<?php

/**
* ownCloud - Provisioning API
*
* @author Tom Needham
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
* You should have received a copy of the GNU Lesser General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

class OC_Provisioning_API_Groups{
	
	/**
	 * returns a list of groups
	 */
	public static function getGroups($parameters){
		return array('groups' => OC_Group::getGroups());
	}
	
	/**
	 * returns an array of users in the group specified
	 */
	public static function getGroup($parameters){
		// Check the group exists
		if(!OC_Group::groupExists($parameters['groupid'])){
			return 101;
		}
		return array('users' => OC_Group::usersInGroup($parameters['groupid']));
	}
	
	/**
	 * creates a new group
	 */
	public static function addGroup($parameters){
		// Validate name
		$groupid = isset($_POST['groupid']) ? $_POST['groupid'] : '';
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $groupid ) || empty($groupid)){
			return 101;
		}
		// Check if it exists
		if(OC_Group::groupExists($groupid)){
			return 102;
		}
		if(OC_Group::createGroup($groupid)){
			return 100;
		} else {
			return 103;
		}
	}
	
	public static function deleteGroup($parameters){
		// Check it exists
		if(!OC_Group::groupExists($parameters['groupid'])){
			return 101;
		} else if($parameters['groupid'] == 'admin'){
			// Cannot delete admin group
			return 102;
		} else {
			if(OC_Group::deleteGroup($parameters['groupid'])){
				return 100;
			} else {
				return 103;
			}
		}
	}
	
}