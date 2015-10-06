<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
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

namespace OCA\Provisioning_API;

use \OC_OCS_Result;
use \OC_SubAdmin;
use \OC_Helper;
use OCP\Files\NotFoundException;

class Users {

	/** @var \OCP\IUserManager */
	private $userManager;

	/** @var \OCP\IConfig */
	private $config;

	/** @var \OCP\IGroupManager */
	private $groupManager;

	/** @var \OCP\IUserSession */
	private $userSession;

	/**
	 * @param \OCP\IUserManager $userManager
	 * @param \OCP\IConfig $config
	 * @param \OCP\IGroupManager $groupManager
	 * @param \OCP\IUserSession $userSession
	 */
	public function __construct(\OCP\IUserManager $userManager,
								\OCP\IConfig $config,
								\OCP\IGroupManager $groupManager,
								\OCP\IUserSession $userSession) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
	}

	/**
	 * returns a list of users
	 *
	 * @return OC_OCS_Result
	 */
	public function getUsers() {
		$search = !empty($_GET['search']) ? $_GET['search'] : '';
		$limit = !empty($_GET['limit']) ? $_GET['limit'] : null;
		$offset = !empty($_GET['offset']) ? $_GET['offset'] : null;

		$users = $this->userManager->search($search, $limit, $offset);
		$users = array_keys($users);

		return new OC_OCS_Result([
			'users' => $users
		]);
	}

	/**
	 * @return OC_OCS_Result
	 */
	public function addUser() {
		$userId = isset($_POST['userid']) ? $_POST['userid'] : null;
		$password = isset($_POST['password']) ? $_POST['password'] : null;
		if($this->userManager->userExists($userId)) {
			\OCP\Util::writeLog('ocs_api', 'Failed addUser attempt: User already exists.', \OCP\Util::ERROR);
			return new OC_OCS_Result(null, 102, 'User already exists');
		} else {
			try {
				$this->userManager->createUser($userId, $password);
				\OCP\Util::writeLog('ocs_api', 'Successful addUser call with userid: '.$_POST['userid'], \OCP\Util::INFO);
				return new OC_OCS_Result(null, 100);
			} catch (\Exception $e) {
				\OCP\Util::writeLog('ocs_api', 'Failed addUser attempt with exception: '.$e->getMessage(), \OCP\Util::ERROR);
				return new OC_OCS_Result(null, 101, 'Bad request');
			}
		}
	}

	/**
	 * gets user info
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getUser($parameters){
		$userId = $parameters['userid'];

		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		// Admin? Or SubAdmin?
		if($this->groupManager->isAdmin($user->getUID()) || OC_SubAdmin::isUserAccessible($user->getUID(), $userId)) {
			// Check they exist
			if(!$this->userManager->userExists($userId)) {
				return new OC_OCS_Result(null, \OCP\API::RESPOND_NOT_FOUND, 'The requested user could not be found');
			}
			// Show all
			$return = [
				'email',
				'enabled',
			];
			if($user->getUID() !== $userId) {
				$return[] = 'quota';
			}
		} else {
			// Check they are looking up themselves
			if($user->getUID() !== $userId) {
				return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
			}
			// Return some additional information compared to the core route
			$return = array(
				'email',
				'displayname',
				);
		}

		// Find the data
		$data = [];
		$data = self::fillStorageInfo($userId, $data);
		$data['enabled'] = $this->config->getUserValue($userId, 'core', 'enabled', 'true');
		$data['email'] = $this->config->getUserValue($userId, 'settings', 'email');
		$data['displayname'] = $this->userManager->get($parameters['userid'])->getDisplayName();

		// Return the appropriate data
		$responseData = array();
		foreach($return as $key) {
			$responseData[$key] = $data[$key];
		}

		return new OC_OCS_Result($responseData);
	}

	/** 
	 * edit users
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function editUser($parameters) {
		$userId = $parameters['userid'];

		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		if($userId === $user->getUID()) {
			// Editing self (display, email)
			$permittedFields[] = 'display';
			$permittedFields[] = 'email';
			$permittedFields[] = 'password';
			// If admin they can edit their own quota
			if($this->groupManager->isAdmin($user->getUID())) {
				$permittedFields[] = 'quota';
			}
		} else {
			// Check if admin / subadmin
			if(OC_SubAdmin::isUserAccessible($user->getUID(), $userId)
			|| $this->groupManager->isAdmin($user->getUID())) {
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
				$this->userManager->get($userId)->setDisplayName($parameters['_put']['value']);
				break;
			case 'quota':
				$quota = $parameters['_put']['value'];
				if($quota !== 'none' and $quota !== 'default') {
					if (is_numeric($quota)) {
						$quota = floatval($quota);
					} else {
						$quota = \OCP\Util::computerFileSize($quota);
					}
					if ($quota === false) {
						return new OC_OCS_Result(null, 103, "Invalid quota value {$parameters['_put']['value']}");
					}
					if($quota === 0) {
						$quota = 'default';
					}else if($quota === -1){
						$quota = 'none';
					} else {
						$quota = \OCP\Util::humanFileSize($quota);
					}
				}
				$this->config->setUserValue($userId, 'files', 'quota', $quota);
				break;
			case 'password':
				$this->userManager->get($userId)->setPassword($parameters['_put']['value']);
				break;
			case 'email':
				if(filter_var($parameters['_put']['value'], FILTER_VALIDATE_EMAIL)) {
					$this->config->setUserValue($userId, 'settings', 'email', $parameters['_put']['value']);
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

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function deleteUser($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		if(!$this->userManager->userExists($parameters['userid']) 
		|| $parameters['userid'] === $user->getUID()) {
			return new OC_OCS_Result(null, 101);
		}
		// If not permitted
		if(!$this->groupManager->isAdmin($user->getUID()) && !OC_SubAdmin::isUserAccessible($user->getUID(), $parameters['userid'])) {
			return new OC_OCS_Result(null, 997);
		}
		// Go ahead with the delete
		if($this->userManager->get($parameters['userid'])->delete()) {
			return new OC_OCS_Result(null, 100);
		} else {
			return new OC_OCS_Result(null, 101);
		}
	}

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getUsersGroups($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		if($parameters['userid'] === $user->getUID() || $this->groupManager->isAdmin($user->getUID())) {
			// Self lookup or admin lookup
			return new OC_OCS_Result([
				'groups' => $this->groupManager->getUserGroupIds(
					$this->userManager->get($parameters['userid'])
				)
			]);
		} else {
			// Looking up someone else
			if(OC_SubAdmin::isUserAccessible($user->getUID(), $parameters['userid'])) {
				// Return the group that the method caller is subadmin of for the user in question
				$groups = array_intersect(
					OC_SubAdmin::getSubAdminsGroups($user->getUID()),
					$this->groupManager->getUserGroupIds(
						$this->userManager->get($parameters['userid'])
					)
				);
				return new OC_OCS_Result(array('groups' => $groups));
			} else {
				// Not permitted
				return new OC_OCS_Result(null, 997);
			}
		}
		
	}

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function addToGroup($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$group = !empty($_POST['groupid']) ? $_POST['groupid'] : null;
		if(is_null($group)){
			return new OC_OCS_Result(null, 101);
		}
		// Check they're an admin
		if(!$this->groupManager->isInGroup($user->getUID(), 'admin')){
			// This user doesn't have rights to add a user to this group
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}
		// Check if the group exists
		if(!$this->groupManager->groupExists($group)){
			return new OC_OCS_Result(null, 102);
		}
		// Check if the user exists
		if(!$this->userManager->userExists($parameters['userid'])){
			return new OC_OCS_Result(null, 103);
		}
		// Add user to group
		$this->groupManager->get($group)->addUser(
			$this->userManager->get($parameters['userid'])
		);
		return new OC_OCS_Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function removeFromGroup($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new OC_OCS_Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$group = !empty($parameters['_delete']['groupid']) ? $parameters['_delete']['groupid'] : null;
		if(is_null($group)){
			return new OC_OCS_Result(null, 101);
		}
		// If they're not an admin, check they are a subadmin of the group in question
		if(!$this->groupManager->isInGroup($user->getUID(), 'admin') && !OC_SubAdmin::isSubAdminofGroup($user->getUID(), $group)){
			return new OC_OCS_Result(null, 104);
		}
		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if($parameters['userid'] === $user->getUID()){
			if($this->groupManager->isInGroup($user->getUID(), 'admin')){
				if($group === 'admin'){
					return new OC_OCS_Result(null, 105, 'Cannot remove yourself from the admin group');
				}
			} else {
				// Not an admin, check they are not removing themself from their subadmin group
				if(in_array($group, OC_SubAdmin::getSubAdminsGroups($user->getUID()))){
					return new OC_OCS_Result(null, 105, 'Cannot remove yourself from this group as you are a SubAdmin');
				}
			}
		}
		// Check if the group exists
		if(!$this->groupManager->groupExists($group)){
			return new OC_OCS_Result(null, 102);
		}
		// Check if the user exists
		if(!$this->userManager->userExists($parameters['userid'])){
			return new OC_OCS_Result(null, 103);
		}
		// Remove user from group
		$this->groupManager->get($group)->removeUser(
			$this->userManager->get($parameters['userid'])
		);
		return new OC_OCS_Result(null, 100);
	}

	/**
	 * Creates a subadmin
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function addSubAdmin($parameters) {
		$group = $_POST['groupid'];
		$user = $parameters['userid'];	
		// Check if the user exists
		if(!$this->userManager->userExists($user)) {
			return new OC_OCS_Result(null, 101, 'User does not exist');
		}
		// Check if group exists
		if(!$this->groupManager->groupExists($group)) {
			return new OC_OCS_Result(null, 102, 'Group:'.$group.' does not exist');
		}
		// Check if trying to make subadmin of admin group
		if(strtolower($group) === 'admin') {
			return new OC_OCS_Result(null, 103, 'Cannot create subadmins for admin group');
		}
		// We cannot be subadmin twice
		if (OC_Subadmin::isSubAdminOfGroup($user, $group)) {
			return new OC_OCS_Result(null, 100);
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
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function removeSubAdmin($parameters) {
		$group = $parameters['_delete']['groupid'];
		$user = $parameters['userid'];	
		// Check if the user exists
		if(!$this->userManager->userExists($user)) {
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
	 * Get the groups a user is a subadmin of
	 *
	 * @param array $parameters
	 * @return OC_OCS_Result
	 */
	public function getUserSubAdminGroups($parameters) {
		$user = $parameters['userid'];
		// Check if the user exists
		if(!$this->userManager->userExists($user)) {
			return new OC_OCS_Result(null, 101, 'User does not exist');
		}
		// Get the subadmin groups
		if(!$groups = OC_SubAdmin::getSubAdminsGroups($user)) {
			return new OC_OCS_Result(null, 102, 'Unknown error occurred');
		} else {
			return new OC_OCS_Result($groups);
		}
	}

	/**
	 * @param string $userId
	 * @param array $data
	 * @return mixed
	 * @throws \OCP\Files\NotFoundException
	 */
	private static function fillStorageInfo($userId, $data) {
		try {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($userId);
			$storage = OC_Helper::getStorageInfo('/');
			$data['quota'] = [
				'free' => $storage['free'],
				'used' => $storage['used'],
				'total' => $storage['total'],
				'relative' => $storage['relative'],
			];
		} catch (NotFoundException $ex) {
			$data['quota'] = [];
		}
		return $data;
	}
}
