<?php

/**
 * ownCloud
 *
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * @author Tom <tom@owncloud.com>
 * @author Thomas Müller <deepdiver@owncloud.com>
 * @author Bart Visscher
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

namespace OCA\Provisioning_API;

use \OC_OCS_Result;
use \OC_SubAdmin;
use \OC_User;
use \OC_Group;
use \OC_Helper;

class Users {

	/**
	 * returns a list of users
	 */
	public static function getUsers(){
		$search = !empty($_GET['search']) ? $_GET['search'] : '';
		$limit = !empty($_GET['limit']) ? $_GET['limit'] : null;
		$offset = !empty($_GET['offset']) ? $_GET['offset'] : null;
		return new OC_OCS_Result(array('users' => OC_User::getUsers($search, $limit, $offset)));
	}

	public static function addUser(){
		$userId = isset($_POST['userid']) ? $_POST['userid'] : null;
		$password = isset($_POST['password']) ? $_POST['password'] : null;
		if(OC_User::userExists($userId)) {
			\OC_Log::write('ocs_api', 'Failed addUser attempt: User already exists.', \OC_Log::ERROR);
			return new OC_OCS_Result(null, 102, 'User already exists');
		} else {
			try {
				OC_User::createUser($userId, $password);
				\OC_Log::write('ocs_api', 'Successful addUser call with userid: '.$_POST['userid'], \OC_Log::INFO);
				return new OC_OCS_Result(null, 100);
			} catch (\Exception $e) {
				\OC_Log::write('ocs_api', 'Failed addUser attempt with exception: '.$e->getMessage(), \OC_Log::ERROR);
				return new OC_OCS_Result(null, 101, 'Bad request');
			}
		}
	}

	/**
	 * gets user info
	 */
	public static function getUser($parameters){
		$userId = $parameters['userid'];
		// Admin? Or SubAdmin?
		if(OC_User::isAdminUser(OC_User::getUser()) || OC_SubAdmin::isUserAccessible(OC_User::getUser(), $userId)) {
			// Check they exist
			if(!OC_User::userExists($userId)) {
				return new OC_OCS_Result(null, \OC_API::RESPOND_NOT_FOUND, 'The requested user could not be found');
			}
			// Show all
			$return = array(
				'email',
				'enabled',
				);
			if(OC_User::getUser() != $userId) {
				$return[] = 'quota';
			}
		} else {
			// Check they are looking up themselves
			if(OC_User::getUser() != $userId) {
				return new OC_OCS_Result(null, \OC_API::RESPOND_UNAUTHORISED);
			}
			// Return some additional information compared to the core route
			$return = array(
				'email',
				'displayname',
				);
		}

		$config = \OC::$server->getConfig();

		// Find the data
		$data = array();
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($userId);
		$storage = OC_Helper::getStorageInfo('/');
		$data['quota'] = array(
			'free' =>  $storage['free'],
			'used' =>  $storage['used'],
			'total' =>  $storage['total'],
			'relative' => $storage['relative'],
			);
		$data['enabled'] = $config->getUserValue($userId, 'core', 'enabled', 'true');
		$data['email'] = $config->getUserValue($userId, 'settings', 'email');
		$data['displayname'] = OC_User::getDisplayName($parameters['userid']);

		// Return the appropriate data
		$responseData = array();
		foreach($return as $key) {
			$responseData[$key] = $data[$key];
		}

		return new OC_OCS_Result($responseData);
	}

	/** 
	 * edit users
	 */
	public static function editUser($parameters){
		$userId = $parameters['userid'];
		if($userId === OC_User::getUser()) {
			// Editing self (display, email)
			$permittedFields[] = 'display';
			$permittedFields[] = 'email';
			$permittedFields[] = 'password';
			// If admin they can edit their own quota
			if(OC_User::isAdminUser(OC_User::getUser())) {
				$permittedFields[] = 'quota';
			}
		} else {
			// Check if admin / subadmin
			if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $userId)
			|| OC_User::isAdminUser(OC_User::getUser())) {
				// They have permissions over the user
				$permittedFields[] = 'display';
				$permittedFields[] = 'quota';
				$permittedFields[] = 'password';
				$permittedFields[] = 'email';
			} else {
				// No rights
				return new OC_OCS_Result(null, 997);
			}
		}
		// Check if permitted to edit this field
		if(!in_array($parameters['_put']['key'], $permittedFields)) {
			return new OC_OCS_Result(null, 997);
		}
		// Process the edit
		switch($parameters['_put']['key']){
			case 'display':
				OC_User::setDisplayName($userId, $parameters['_put']['value']);
				break;
			case 'quota':
				$quota = $parameters['_put']['value'];
				if($quota !== 'none' and $quota !== 'default') {
					$quota = OC_Helper::computerFileSize($quota);
					if($quota == 0) {
						$quota = 'default';
					}else if($quota == -1){
						$quota = 'none';
					} else {
						$quota = OC_Helper::humanFileSize($quota);
					}
				}
				\OC::$server->getConfig()->setUserValue($userId, 'files', 'quota', $quota);
				break;
			case 'password':
				OC_User::setPassword($userId, $parameters['_put']['value']);
				break;
			case 'email':
				if(filter_var($parameters['_put']['value'], FILTER_VALIDATE_EMAIL)) {
					\OC::$server->getConfig()->setUserValue($userId, 'settings', 'email', $parameters['_put']['value']);
				} else {
					return new OC_OCS_Result(null, 102);
				}
				break;
			default:
				return new OC_OCS_Result(null, 103);
				break;
		}
		return new OC_OCS_Result(null, 100);
	}

	public static function deleteUser($parameters){
		if(!OC_User::userExists($parameters['userid']) 
		|| $parameters['userid'] === OC_User::getUser()) {
			return new OC_OCS_Result(null, 101);
		}
		// If not permitted
		if(!OC_User::isAdminUser(OC_User::getUser()) && !OC_SubAdmin::isUserAccessible(OC_User::getUser(), $parameters['userid'])) {
			return new OC_OCS_Result(null, 997);
		}
		// Go ahead with the delete
		if(OC_User::deleteUser($parameters['userid'])) {
			return new OC_OCS_Result(null, 100);
		} else {
			return new OC_OCS_Result(null, 101);
		}
	}

	public static function getUsersGroups($parameters){
		if($parameters['userid'] === OC_User::getUser() || OC_User::isAdminUser(OC_User::getUser())) {
			// Self lookup or admin lookup
			return new OC_OCS_Result(array('groups' => OC_Group::getUserGroups($parameters['userid'])));
		} else {
			// Looking up someone else
			if(OC_SubAdmin::isUserAccessible(OC_User::getUser(), $parameters['userid'])) {
				// Return the group that the method caller is subadmin of for the user in question
				$groups = array_intersect(OC_SubAdmin::getSubAdminsGroups(OC_User::getUser()), OC_Group::getUserGroups($parameters['userid']));
				return new OC_OCS_Result(array('groups' => $groups));
			} else {
				// Not permitted
				return new OC_OCS_Result(null, 997);
			}
		}
		
	}

	public static function addToGroup($parameters){
		$group = !empty($_POST['groupid']) ? $_POST['groupid'] : null;
		if(is_null($group)){
			return new OC_OCS_Result(null, 101);
		}
		// Check they're an admin
		if(!OC_Group::inGroup(OC_User::getUser(), 'admin')){
			// This user doesn't have rights to add a user to this group
			return new OC_OCS_Result(null, \OC_API::RESPOND_UNAUTHORISED);
		}
		// Check if the group exists
		if(!OC_Group::groupExists($group)){
			return new OC_OCS_Result(null, 102);
		}
		// Check if the user exists
		if(!OC_User::userExists($parameters['userid'])){
			return new OC_OCS_Result(null, 103);
		}
		// Add user to group
		return OC_Group::addToGroup($parameters['userid'], $group) ? new OC_OCS_Result(null, 100) : new OC_OCS_Result(null, 105);
	}

	public static function removeFromGroup($parameters){
		$group = !empty($parameters['_delete']['groupid']) ? $parameters['_delete']['groupid'] : null;
		if(is_null($group)){
			return new OC_OCS_Result(null, 101);
		}
		// If they're not an admin, check they are a subadmin of the group in question
		if(!OC_Group::inGroup(OC_User::getUser(), 'admin') && !OC_SubAdmin::isSubAdminofGroup(OC_User::getUser(), $group)){
			return new OC_OCS_Result(null, 104);
		}
		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if($parameters['userid'] === OC_User::getUser()){
			if(OC_Group::inGroup(OC_User::getUser(), 'admin')){
				if($group === 'admin'){
					return new OC_OCS_Result(null, 105, 'Cannot remove yourself from the admin group');
				}
			} else {
				// Not an admin, check they are not removing themself from their subadmin group
				if(in_array($group, OC_SubAdmin::getSubAdminsGroups(OC_User::getUser()))){
					return new OC_OCS_Result(null, 105, 'Cannot remove yourself from this group as you are a SubAdmin');
				}
			}
		}
		// Check if the group exists
		if(!OC_Group::groupExists($group)){
			return new OC_OCS_Result(null, 102);
		}
		// Check if the user exists
		if(!OC_User::userExists($parameters['userid'])){
			return new OC_OCS_Result(null, 103);
		}
		// Remove user from group
		return OC_Group::removeFromGroup($parameters['userid'], $group) ? new OC_OCS_Result(null, 100) : new OC_OCS_Result(null, 105);
	}

	/**
	 * Creates a subadmin
	 */
	public static function addSubAdmin($parameters) {
		$group = $_POST['groupid'];
		$user = $parameters['userid'];	
		// Check if the user exists
		if(!OC_User::userExists($user)) {
			return new OC_OCS_Result(null, 101, 'User does not exist');
		}
		// Check if group exists
		if(!OC_Group::groupExists($group)) {
			return new OC_OCS_Result(null, 102, 'Group:'.$group.' does not exist');
		}
		// Check if trying to make subadmin of admin group
		if(strtolower($group) == 'admin') {
			return new OC_OCS_Result(null, 103, 'Cannot create subadmins for admin group');
		}
		// Go
		if(OC_Subadmin::createSubAdmin($user, $group)) {
			return new OC_OCS_Result(null, 100);
		} else {
			return new OC_OCS_Result(null, 103, 'Unknown error occured');
		}

	}

	/**
	 * Removes a subadmin from a group
	 */
	public static function removeSubAdmin($parameters) {
		$group = $parameters['_delete']['groupid'];
		$user = $parameters['userid'];	
		// Check if the user exists
		if(!OC_User::userExists($user)) {
			return new OC_OCS_Result(null, 101, 'User does not exist');
		}
		// Check if they are a subadmin of this said group
		if(!OC_SubAdmin::isSubAdminofGroup($user, $group)) {
			return new OC_OCS_Result(null, 102, 'User is not a subadmin of this group');
		}
		// Go
		if(OC_Subadmin::deleteSubAdmin($user, $group)) {
			return new OC_OCS_Result(null, 100);
		} else {
			return new OC_OCS_Result(null, 103, 'Unknown error occurred');
		}
	}

	/**
	 * @Get the groups a user is a subadmin of
	 */
	public static function getUserSubAdminGroups($parameters) {
		$user = $parameters['userid'];
		// Check if the user exists
		if(!OC_User::userExists($user)) {
			return new OC_OCS_Result(null, 101, 'User does not exist');
		}
		// Get the subadmin groups
		if(!$groups = OC_SubAdmin::getSubAdminsGroups($user)) {
			return new OC_OCS_Result(null, 102, 'Unknown error occurred');
		} else {
			return new OC_OCS_Result($groups);
		}
	}
}
