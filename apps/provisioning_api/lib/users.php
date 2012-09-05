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

class OC_Provisioning_API_Users {
	
	/**
	 * returns a list of users
	 */
	public static function getUsers($parameters){
		return OC_User::getUsers();
	}
	
	public static function addUser(){
		$userid = isset($_POST['userid']) ? $_POST['userid'] : null;
		$password = isset($_POST['password']) ? $_POST['password'] : null;
		try {
			OC_User::createUser($userid, $password);
			return 100;
		} catch (Exception $e) {
			switch($e->getMessage()){
				case 'Only the following characters are allowed in a username: "a-z", "A-Z", "0-9", and "_.@-"':
				case 'A valid username must be provided':
				case 'A valid password must be provided':
					return 101;
					break;
				case 'The username is already being used';
					return 102;
					break;
				default:
					return 103;
					break;
			}
		}
	}
	
	/**
	 * gets user info
	 */
	public static function getUser($parameters){
		$userid = $parameters['userid'];
		$return = array();
		$return['email'] = OC_Preferences::getValue($userid, 'settings', 'email', '');
		$default = OC_Appconfig::getValue('files', 'default_quota', 0);
		$return['quota'] = OC_Preferences::getValue($userid, 'files', 'quota', $default);
		return $return;
	}
	
	public static function editUser($parameters){
		
	}
	
	public static function deleteUser($parameters){
		
	}
	
	public static function getSharedWithUser($parameters){
		
	}
	
	public static function getSharedByUser($parameters){
		
	}
	
	public static function deleteSharedByUser($parameters){
		
	}
	
	public static function getUsersGroups($parameters){
		$userid = $parameters['userid'];
		return array('groups' => OC_Group::getUserGroups($userid));
	}
	
	public static function addToGroup($parameters){
		
	}
	
	public static function removeFromGroup($parameters){
		
	}
	
}