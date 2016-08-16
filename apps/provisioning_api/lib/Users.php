<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author michag86 <micha_g@arcor.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Tom Needham <tom@owncloud.com>
 *
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

use \OC_Helper;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IUserSession;

class Users {

	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var IGroupManager|\OC\Group\Manager */ // FIXME Requires a method that is not on the interface
	private $groupManager;
	/** @var IUserSession */
	private $userSession;
	/** @var ILogger */
	private $logger;

	/**
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param ILogger $logger
	 */
	public function __construct(IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								ILogger $logger) {
		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->logger = $logger;
	}

	/**
	 * returns a list of users
	 *
	 * @return \OC\OCS\Result
	 */
	public function getUsers() {
		$search = !empty($_GET['search']) ? $_GET['search'] : '';
		$limit = !empty($_GET['limit']) ? $_GET['limit'] : null;
		$offset = !empty($_GET['offset']) ? $_GET['offset'] : null;

		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		// Admin? Or SubAdmin?
		$uid = $user->getUID();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if($this->groupManager->isAdmin($uid)){
			$users = $this->userManager->search($search, $limit, $offset);
		} else if ($subAdminManager->isSubAdmin($user)) {
			$subAdminOfGroups = $subAdminManager->getSubAdminsGroups($user);
			foreach ($subAdminOfGroups as $key => $group) {
				$subAdminOfGroups[$key] = $group->getGID();
			}

			if($offset === null) {
				$offset = 0; 
			}

			$users = [];
			foreach ($subAdminOfGroups as $group) {
				$users = array_merge($users, $this->groupManager->displayNamesInGroup($group, $search));
			}

			$users = array_slice($users, $offset, $limit);
		} else {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}
		$users = array_keys($users);

		return new \OC\OCS\Result([
			'users' => $users
		]);
	}

	/**
	 * @return \OC\OCS\Result
	 */
	public function addUser() {
		$userId = isset($_POST['userid']) ? $_POST['userid'] : null;
		$password = isset($_POST['password']) ? $_POST['password'] : null;
		$groups = isset($_POST['groups']) ? $_POST['groups'] : null;
		$user = $this->userSession->getUser();
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$subAdminManager = $this->groupManager->getSubAdmin();

		if (!$isAdmin && !$subAdminManager->isSubAdmin($user)) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		if($this->userManager->userExists($userId)) {
			$this->logger->error('Failed addUser attempt: User already exists.', ['app' => 'ocs_api']);
			return new \OC\OCS\Result(null, 102, 'User already exists');
		}

		if(is_array($groups)) {
			foreach ($groups as $group) {
				if(!$this->groupManager->groupExists($group)){
					return new \OC\OCS\Result(null, 104, 'group '.$group.' does not exist');
				}
				if(!$isAdmin && !$subAdminManager->isSubAdminofGroup($user, $this->groupManager->get($group))) {
					return new \OC\OCS\Result(null, 105, 'insufficient privileges for group '. $group);
				}
			}
		} else {
			if(!$isAdmin) {
				return new \OC\OCS\Result(null, 106, 'no group specified (required for subadmins)');
			}
		}
		
		try {
			$newUser = $this->userManager->createUser($userId, $password);
			$this->logger->info('Successful addUser call with userid: '.$userId, ['app' => 'ocs_api']);

			if (is_array($groups)) {
				foreach ($groups as $group) {
					$this->groupManager->get($group)->addUser($newUser);
					$this->logger->info('Added userid '.$userId.' to group '.$group, ['app' => 'ocs_api']);
				}
			}
			return new \OC\OCS\Result(null, 100);
		} catch (\Exception $e) {
			$this->logger->error('Failed addUser attempt with exception: '.$e->getMessage(), ['app' => 'ocs_api']);
			return new \OC\OCS\Result(null, 101, 'Bad request');
		}
	}

	/**
	 * gets user info
	 *
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function getUser($parameters) {
		$userId = $parameters['userid'];

		// Check if user is logged in
		$currentLoggedInUser = $this->userSession->getUser();
		if ($currentLoggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$data = [];

		// Check if the target user exists
		$targetUserObject = $this->userManager->get($userId);
		if($targetUserObject === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_NOT_FOUND, 'The requested user could not be found');
		}

		// Admin? Or SubAdmin?
		if($this->groupManager->isAdmin($currentLoggedInUser->getUID())
			|| $this->groupManager->getSubAdmin()->isUserAccessible($currentLoggedInUser, $targetUserObject)) {
			$data['enabled'] = $this->config->getUserValue($userId, 'core', 'enabled', 'true');
		} else {
			// Check they are looking up themselves
			if($currentLoggedInUser->getUID() !== $userId) {
				return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
			}
		}

		// Find the data
		$data['quota'] = $this->fillStorageInfo($userId);
		$data['email'] = $targetUserObject->getEMailAddress();
		$data['displayname'] = $targetUserObject->getDisplayName();

		return new \OC\OCS\Result($data);
	}

	/** 
	 * edit users
	 *
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function editUser($parameters) {
		/** @var string $targetUserId */
		$targetUserId = $parameters['userid'];

		// Check if user is logged in
		$currentLoggedInUser = $this->userSession->getUser();
		if ($currentLoggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$targetUser = $this->userManager->get($targetUserId);
		if($targetUser === null) {
			return new \OC\OCS\Result(null, 997);
		}

		$permittedFields = [];
		if($targetUserId === $currentLoggedInUser->getUID()) {
			// Editing self (display, email)
			$permittedFields[] = 'display';
			$permittedFields[] = 'email';
			$permittedFields[] = 'password';
			// If admin they can edit their own quota
			if($this->groupManager->isAdmin($currentLoggedInUser->getUID())) {
				$permittedFields[] = 'quota';
			}
		} else {
			// Check if admin / subadmin
			$subAdminManager = $this->groupManager->getSubAdmin();
			if($subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)
			|| $this->groupManager->isAdmin($currentLoggedInUser->getUID())) {
				// They have permissions over the user
				$permittedFields[] = 'display';
				$permittedFields[] = 'quota';
				$permittedFields[] = 'password';
				$permittedFields[] = 'email';
			} else {
				// No rights
				return new \OC\OCS\Result(null, 997);
			}
		}
		// Check if permitted to edit this field
		if(!in_array($parameters['_put']['key'], $permittedFields)) {
			return new \OC\OCS\Result(null, 997);
		}
		// Process the edit
		switch($parameters['_put']['key']) {
			case 'display':
				$targetUser->setDisplayName($parameters['_put']['value']);
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
						return new \OC\OCS\Result(null, 103, "Invalid quota value {$parameters['_put']['value']}");
					}
					if($quota === 0) {
						$quota = 'default';
					}else if($quota === -1) {
						$quota = 'none';
					} else {
						$quota = \OCP\Util::humanFileSize($quota);
					}
				}
				$targetUser->setQuota($quota);
				break;
			case 'password':
				$targetUser->setPassword($parameters['_put']['value']);
				break;
			case 'email':
				if(filter_var($parameters['_put']['value'], FILTER_VALIDATE_EMAIL)) {
					$targetUser->setEMailAddress($parameters['_put']['value']);
				} else {
					return new \OC\OCS\Result(null, 102);
				}
				break;
			default:
				return new \OC\OCS\Result(null, 103);
		}
		return new \OC\OCS\Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function deleteUser($parameters) {
		// Check if user is logged in
		$currentLoggedInUser = $this->userSession->getUser();
		if ($currentLoggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$targetUser = $this->userManager->get($parameters['userid']);

		if($targetUser === null || $targetUser->getUID() === $currentLoggedInUser->getUID()) {
			return new \OC\OCS\Result(null, 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			return new \OC\OCS\Result(null, 997);
		}

		// Go ahead with the delete
		if($targetUser->delete()) {
			return new \OC\OCS\Result(null, 100);
		} else {
			return new \OC\OCS\Result(null, 101);
		}
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function disableUser($parameters) {
		return $this->setEnabled($parameters, false);
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function enableUser($parameters) {
		return $this->setEnabled($parameters, true);
	}

	/**
	 * @param array $parameters
	 * @param bool $value
	 * @return \OC\OCS\Result
	 */
	private function setEnabled($parameters, $value) {
		// Check if user is logged in
		$currentLoggedInUser = $this->userSession->getUser();
		if ($currentLoggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$targetUser = $this->userManager->get($parameters['userid']);
		if($targetUser === null || $targetUser->getUID() === $currentLoggedInUser->getUID()) {
			return new \OC\OCS\Result(null, 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			return new \OC\OCS\Result(null, 997);
		}

		// enable/disable the user now
		$targetUser->setEnabled($value);
		return new \OC\OCS\Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function getUsersGroups($parameters) {
		// Check if user is logged in
		$loggedInUser = $this->userSession->getUser();
		if ($loggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$targetUser = $this->userManager->get($parameters['userid']);
		if($targetUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_NOT_FOUND);
		}

		if($targetUser->getUID() === $loggedInUser->getUID() || $this->groupManager->isAdmin($loggedInUser->getUID())) {
			// Self lookup or admin lookup
			return new \OC\OCS\Result([
				'groups' => $this->groupManager->getUserGroupIds($targetUser)
			]);
		} else {
			$subAdminManager = $this->groupManager->getSubAdmin();

			// Looking up someone else
			if($subAdminManager->isUserAccessible($loggedInUser, $targetUser)) {
				// Return the group that the method caller is subadmin of for the user in question
				$getSubAdminsGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
				foreach ($getSubAdminsGroups as $key => $group) {
					$getSubAdminsGroups[$key] = $group->getGID();
				}
				$groups = array_intersect(
					$getSubAdminsGroups,
					$this->groupManager->getUserGroupIds($targetUser)
				);
				return new \OC\OCS\Result(array('groups' => $groups));
			} else {
				// Not permitted
				return new \OC\OCS\Result(null, 997);
			}
		}
		
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function addToGroup($parameters) {
		// Check if user is logged in
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		// Check they're an admin
		if(!$this->groupManager->isAdmin($user->getUID())) {
			// This user doesn't have rights to add a user to this group
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$groupId = !empty($_POST['groupid']) ? $_POST['groupid'] : null;
		if($groupId === null) {
			return new \OC\OCS\Result(null, 101);
		}

		$group = $this->groupManager->get($groupId);
		$targetUser = $this->userManager->get($parameters['userid']);
		if($group === null) {
			return new \OC\OCS\Result(null, 102);
		}
		if($targetUser === null) {
			return new \OC\OCS\Result(null, 103);
		}

		// Add user to group
		$group->addUser($targetUser);
		return new \OC\OCS\Result(null, 100);
	}

	/**
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function removeFromGroup($parameters) {
		// Check if user is logged in
		$loggedInUser = $this->userSession->getUser();
		if ($loggedInUser === null) {
			return new \OC\OCS\Result(null, \OCP\API::RESPOND_UNAUTHORISED);
		}

		$group = !empty($parameters['_delete']['groupid']) ? $parameters['_delete']['groupid'] : null;
		if($group === null) {
			return new \OC\OCS\Result(null, 101);
		}

		$group = $this->groupManager->get($group);
		if($group === null) {
			return new \OC\OCS\Result(null, 102);
		}

		$targetUser = $this->userManager->get($parameters['userid']);
		if($targetUser === null) {
			return new \OC\OCS\Result(null, 103);
		}

		// If they're not an admin, check they are a subadmin of the group in question
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminofGroup($loggedInUser, $group)) {
			return new \OC\OCS\Result(null, 104);
		}
		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if($parameters['userid'] === $loggedInUser->getUID()) {
			if($this->groupManager->isAdmin($loggedInUser->getUID())) {
				if($group->getGID() === 'admin') {
					return new \OC\OCS\Result(null, 105, 'Cannot remove yourself from the admin group');
				}
			} else {
				// Not an admin, check they are not removing themself from their subadmin group
				$subAdminGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
				foreach ($subAdminGroups as $key => $group) {
					$subAdminGroups[$key] = $group->getGID();
				}

				if(in_array($group->getGID(), $subAdminGroups, true)) {
					return new \OC\OCS\Result(null, 105, 'Cannot remove yourself from this group as you are a SubAdmin');
				}
			}
		}

		// Remove user from group
		$group->removeUser($targetUser);
		return new \OC\OCS\Result(null, 100);
	}

	/**
	 * Creates a subadmin
	 *
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function addSubAdmin($parameters) {
		$group = $this->groupManager->get($_POST['groupid']);
		$user = $this->userManager->get($parameters['userid']);

		// Check if the user exists
		if($user === null) {
			return new \OC\OCS\Result(null, 101, 'User does not exist');
		}
		// Check if group exists
		if($group === null) {
			return new \OC\OCS\Result(null, 102, 'Group:'.$_POST['groupid'].' does not exist');
		}
		// Check if trying to make subadmin of admin group
		if(strtolower($_POST['groupid']) === 'admin') {
			return new \OC\OCS\Result(null, 103, 'Cannot create subadmins for admin group');
		}

		$subAdminManager = $this->groupManager->getSubAdmin();

		// We cannot be subadmin twice
		if ($subAdminManager->isSubAdminofGroup($user, $group)) {
			return new \OC\OCS\Result(null, 100);
		}
		// Go
		if($subAdminManager->createSubAdmin($user, $group)) {
			return new \OC\OCS\Result(null, 100);
		} else {
			return new \OC\OCS\Result(null, 103, 'Unknown error occurred');
		}
	}

	/**
	 * Removes a subadmin from a group
	 *
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function removeSubAdmin($parameters) {
		$group = $this->groupManager->get($parameters['_delete']['groupid']);
		$user = $this->userManager->get($parameters['userid']);
		$subAdminManager = $this->groupManager->getSubAdmin();

		// Check if the user exists
		if($user === null) {
			return new \OC\OCS\Result(null, 101, 'User does not exist');
		}
		// Check if the group exists
		if($group === null) {
			return new \OC\OCS\Result(null, 101, 'Group does not exist');
		}
		// Check if they are a subadmin of this said group
		if(!$subAdminManager->isSubAdminofGroup($user, $group)) {
			return new \OC\OCS\Result(null, 102, 'User is not a subadmin of this group');
		}

		// Go
		if($subAdminManager->deleteSubAdmin($user, $group)) {
			return new \OC\OCS\Result(null, 100);
		} else {
			return new \OC\OCS\Result(null, 103, 'Unknown error occurred');
		}
	}

	/**
	 * Get the groups a user is a subadmin of
	 *
	 * @param array $parameters
	 * @return \OC\OCS\Result
	 */
	public function getUserSubAdminGroups($parameters) {
		$user = $this->userManager->get($parameters['userid']);
		// Check if the user exists
		if($user === null) {
			return new \OC\OCS\Result(null, 101, 'User does not exist');
		}

		// Get the subadmin groups
		$groups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($user);
		foreach ($groups as $key => $group) {
			$groups[$key] = $group->getGID();
		}

		if(!$groups) {
			return new \OC\OCS\Result(null, 102, 'Unknown error occurred');
		} else {
			return new \OC\OCS\Result($groups);
		}
	}

	/**
	 * @param string $userId
	 * @return array
	 * @throws \OCP\Files\NotFoundException
	 */
	protected function fillStorageInfo($userId) {
		try {
			\OC_Util::tearDownFS();
			\OC_Util::setupFS($userId);
			$storage = OC_Helper::getStorageInfo('/');
			$data = [
				'free' => $storage['free'],
				'used' => $storage['used'],
				'total' => $storage['total'],
				'relative' => $storage['relative'],
				'quota' => $storage['quota'],
			];
		} catch (NotFoundException $ex) {
			$data = [];
		}
		return $data;
	}
}
