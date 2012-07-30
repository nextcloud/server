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
		$groups = OC_Group::getGroups();
		return empty($groups) ? 404 : $groups;
	}
	
	/**
	 * returns an array of users in the group specified
	 */
	public static function getGroup($parameters){
		// Check the group exists
		if(!OC_Group::groupExists($parameters['groupid'])){
			return 404;
		}
		return OC_Group::usersInGroup($parameters['groupid']);
	}
	
	/**
	 * creates a new group
	 */
	public static function addGroup($parameters){
		// Validate name
		if( preg_match( '/[^a-zA-Z0-9 _\.@\-]/', $parameters['groupid'] ) || empty($parameters['groupid'])){
			return 401;
		}
		// Check if it exists
		if(OC_Group::groupExists($parameters['groupid'])){
			return 409;
		}
		if(OC_Group::createGroup($parameters['groupid'])){
			return 200;
		} else {
			return 500;
		}
	}
	
	public static function deleteGroup($parameters){
		// Check it exists
		if(!OC_Group::groupExists($parameters['groupid'])){
			return 404;
		} else if($parameters['groupid'] == 'admin'){
			// Cannot delete admin group
			return 403;
		} else {
			if(OC_Group::deleteGroup($parameters['groupid'])){
				return 200;
			} else {
				return 500;
			}
		}
	}
	
}