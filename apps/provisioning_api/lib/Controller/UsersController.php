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

namespace OCA\Provisioning_API\Controller;

use OC\Accounts\AccountManager;
use \OC_Helper;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class UsersController extends OCSController {

	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var IGroupManager|\OC\Group\Manager */ // FIXME Requires a method that is not on the interface
	private $groupManager;
	/** @var IUserSession */
	private $userSession;
	/** @var AccountManager */
	private $accountManager;
	/** @var ILogger */
	private $logger;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param AccountManager $accountManager
	 * @param ILogger $logger
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IGroupManager $groupManager,
								IUserSession $userSession,
								AccountManager $accountManager,
								ILogger $logger) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
		$this->logger = $logger;
	}

	/**
	 * @NoAdminRequired
	 *
	 * returns a list of users
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @return DataResponse
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		$user = $this->userSession->getUser();
		$users = [];

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
		}

		$users = array_keys($users);

		return new DataResponse([
			'users' => $users
		]);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * @param string $userid
	 * @param string $password
	 * @param array $groups
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addUser($userid, $password, $groups = null) {
		$user = $this->userSession->getUser();
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$subAdminManager = $this->groupManager->getSubAdmin();

		if($this->userManager->userExists($userid)) {
			$this->logger->error('Failed addUser attempt: User already exists.', ['app' => 'ocs_api']);
			throw new OCSException('User already exists', 102);
		}

		if(is_array($groups)) {
			foreach ($groups as $group) {
				if(!$this->groupManager->groupExists($group)) {
					throw new OCSException('group '.$group.' does not exist', 104);
				}
				if(!$isAdmin && !$subAdminManager->isSubAdminofGroup($user, $this->groupManager->get($group))) {
					throw new OCSException('insufficient privileges for group '. $group, 105);
				}
			}
		} else {
			if(!$isAdmin) {
				throw new OCSException('no group specified (required for subadmins)', 106);
			}
		}

		try {
			$newUser = $this->userManager->createUser($userid, $password);
			$this->logger->info('Successful addUser call with userid: '.$userid, ['app' => 'ocs_api']);

			if (is_array($groups)) {
				foreach ($groups as $group) {
					$this->groupManager->get($group)->addUser($newUser);
					$this->logger->info('Added userid '.$userid.' to group '.$group, ['app' => 'ocs_api']);
				}
			}
			return new DataResponse();
		} catch (\Exception $e) {
			$this->logger->error('Failed addUser attempt with exception: '.$e->getMessage(), ['app' => 'ocs_api']);
			throw new OCSException('Bad request', 101);
		}
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * gets user info
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getUser($userId) {
		$data = $this->getUserData($userId);
		return new DataResponse($data);
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * gets user info from the currently logged in user
	 *
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getCurrentUser() {
		$user = $this->userSession->getUser();
		if ($user) {
			$data =  $this->getUserData($user->getUID());
			// rename "displayname" to "display-name" only for this call to keep
			// the API stable.
			$data['display-name'] = $data['displayname'];
			unset($data['displayname']);
			return new DataResponse($data);

		}

		throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
	}

	/**
	 * creates a array with all user data
	 *
	 * @param $userId
	 * @return array
	 * @throws OCSException
	 */
	protected function getUserData($userId) {
		$currentLoggedInUser = $this->userSession->getUser();

		$data = [];

		// Check if the target user exists
		$targetUserObject = $this->userManager->get($userId);
		if($targetUserObject === null) {
			throw new OCSException('The requested user could not be found', \OCP\API::RESPOND_NOT_FOUND);
		}

		// Admin? Or SubAdmin?
		if($this->groupManager->isAdmin($currentLoggedInUser->getUID())
			|| $this->groupManager->getSubAdmin()->isUserAccessible($currentLoggedInUser, $targetUserObject)) {
			$data['enabled'] = $this->config->getUserValue($userId, 'core', 'enabled', 'true');
		} else {
			// Check they are looking up themselves
			if($currentLoggedInUser->getUID() !== $userId) {
				throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
			}
		}

		$userAccount = $this->accountManager->getUser($targetUserObject);

		// Find the data
		$data['id'] = $targetUserObject->getUID();
		$data['quota'] = $this->fillStorageInfo($userId);
		$data['email'] = $targetUserObject->getEMailAddress();
		$data['displayname'] = $targetUserObject->getDisplayName();
		$data['phone'] = $userAccount[\OC\Accounts\AccountManager::PROPERTY_PHONE]['value'];
		$data['address'] = $userAccount[\OC\Accounts\AccountManager::PROPERTY_ADDRESS]['value'];
		$data['webpage'] = $userAccount[\OC\Accounts\AccountManager::PROPERTY_WEBSITE]['value'];
		$data['twitter'] = $userAccount[\OC\Accounts\AccountManager::PROPERTY_TWITTER]['value'];

		return $data;
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * edit users
	 *
	 * @param string $userId
	 * @param string $key
	 * @param string $value
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function editUser($userId, $key, $value) {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if($targetUser === null) {
			throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
		}

		$permittedFields = [];
		if($userId === $currentLoggedInUser->getUID()) {
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
				throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
			}
		}
		// Check if permitted to edit this field
		if(!in_array($key, $permittedFields)) {
			throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
		}
		// Process the edit
		switch($key) {
			case 'display':
				$targetUser->setDisplayName($value);
				break;
			case 'quota':
				$quota = $value;
				if($quota !== 'none' and $quota !== 'default') {
					if (is_numeric($quota)) {
						$quota = floatval($quota);
					} else {
						$quota = \OCP\Util::computerFileSize($quota);
					}
					if ($quota === false) {
						throw new OCSException('Invalid quota value '.$value, 103);
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
				$targetUser->setPassword($value);
				break;
			case 'email':
				if(filter_var($value, FILTER_VALIDATE_EMAIL)) {
					$targetUser->setEMailAddress($value);
				} else {
					throw new OCSException('', 102);
				}
				break;
			default:
				throw new OCSException('', 103);
		}
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function deleteUser($userId) {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);

		if($targetUser === null || $targetUser->getUID() === $currentLoggedInUser->getUID()) {
			throw new OCSException('', 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
		}

		// Go ahead with the delete
		if($targetUser->delete()) {
			return new DataResponse();
		} else {
			throw new OCSException('', 101);
		}
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function disableUser($userId) {
		return $this->setEnabled($userId, false);
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	public function enableUser($userId) {
		return $this->setEnabled($userId, true);
	}

	/**
	 * @param string $userId
	 * @param bool $value
	 * @return DataResponse
	 * @throws OCSException
	 * @throws OCSForbiddenException
	 */
	private function setEnabled($userId, $value) {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if($targetUser === null || $targetUser->getUID() === $currentLoggedInUser->getUID()) {
			throw new OCSException('', 101);
		}

		// If not permitted
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($currentLoggedInUser->getUID()) && !$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)) {
			throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
		}

		// enable/disable the user now
		$targetUser->setEnabled($value);
		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoSubAdminRequired
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getUsersGroups($userId) {
		$loggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if($targetUser === null) {
			throw new OCSException('', \OCP\API::RESPOND_NOT_FOUND);
		}

		if($targetUser->getUID() === $loggedInUser->getUID() || $this->groupManager->isAdmin($loggedInUser->getUID())) {
			// Self lookup or admin lookup
			return new DataResponse([
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
				return new DataResponse(['groups' => $groups]);
			} else {
				// Not permitted
				throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
			}
		}

	}

	/**
	 * @PasswordConfirmationRequired
	 * @param string $userId
	 * @param string $groupid
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addToGroup($userId, $groupid = '') {
		if($groupid === '') {
			throw new OCSException('', 101);
		}

		$group = $this->groupManager->get($groupid);
		$targetUser = $this->userManager->get($userId);
		if($group === null) {
			throw new OCSException('', 102);
		}
		if($targetUser === null) {
			throw new OCSException('', 103);
		}

		// Add user to group
		$group->addUser($targetUser);
		return new DataResponse();
	}

	/**
	 * @PasswordConfirmationRequired
	 * @NoAdminRequired
	 *
	 * @param string $userId
	 * @param string $groupid
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function removeFromGroup($userId, $groupid) {
		$loggedInUser = $this->userSession->getUser();

		if($groupid === null) {
			throw new OCSException('', 101);
		}

		$group = $this->groupManager->get($groupid);
		if($group === null) {
			throw new OCSException('', 102);
		}

		$targetUser = $this->userManager->get($userId);
		if($targetUser === null) {
			throw new OCSException('', 103);
		}

		// If they're not an admin, check they are a subadmin of the group in question
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminofGroup($loggedInUser, $group)) {
			throw new OCSException('', 104);
		}
		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if($userId === $loggedInUser->getUID()) {
			if($this->groupManager->isAdmin($loggedInUser->getUID())) {
				if($group->getGID() === 'admin') {
					throw new OCSException('Cannot remove yourself from the admin group', 105);
				}
			} else {
				// Not an admin, check they are not removing themself from their subadmin group
				$subAdminGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
				foreach ($subAdminGroups as $key => $group) {
					$subAdminGroups[$key] = $group->getGID();
				}

				if(in_array($group->getGID(), $subAdminGroups, true)) {
					throw new OCSException('Cannot remove yourself from this group as you are a SubAdmin', 105);
				}
			}
		}

		// Remove user from group
		$group->removeUser($targetUser);
		return new DataResponse();
	}

	/**
	 * Creates a subadmin
	 *
	 * @PasswordConfirmationRequired
	 *
	 * @param string $userId
	 * @param string $groupid
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function addSubAdmin($userId, $groupid) {
		$group = $this->groupManager->get($groupid);
		$user = $this->userManager->get($userId);

		// Check if the user exists
		if($user === null) {
			throw new OCSException('User does not exist', 101);
		}
		// Check if group exists
		if($group === null) {
			throw new OCSException('Group:'.$groupid.' does not exist',  102);
		}
		// Check if trying to make subadmin of admin group
		if(strtolower($groupid) === 'admin') {
			throw new OCSException('Cannot create subadmins for admin group', 103);
		}

		$subAdminManager = $this->groupManager->getSubAdmin();

		// We cannot be subadmin twice
		if ($subAdminManager->isSubAdminofGroup($user, $group)) {
			return new DataResponse();
		}
		// Go
		if($subAdminManager->createSubAdmin($user, $group)) {
			return new DataResponse();
		} else {
			throw new OCSException('Unknown error occurred', 103);
		}
	}

	/**
	 * Removes a subadmin from a group
	 *
	 * @PasswordConfirmationRequired
	 *
	 * @param string $userId
	 * @param string $groupid
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function removeSubAdmin($userId, $groupid) {
		$group = $this->groupManager->get($groupid);
		$user = $this->userManager->get($userId);
		$subAdminManager = $this->groupManager->getSubAdmin();

		// Check if the user exists
		if($user === null) {
			throw new OCSException('User does not exist', 101);
		}
		// Check if the group exists
		if($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		// Check if they are a subadmin of this said group
		if(!$subAdminManager->isSubAdminofGroup($user, $group)) {
			throw new OCSException('User is not a subadmin of this group', 102);
		}

		// Go
		if($subAdminManager->deleteSubAdmin($user, $group)) {
			return new DataResponse();
		} else {
			throw new OCSException('Unknown error occurred', 103);
		}
	}

	/**
	 * Get the groups a user is a subadmin of
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function getUserSubAdminGroups($userId) {
		$user = $this->userManager->get($userId);
		// Check if the user exists
		if($user === null) {
			throw new OCSException('User does not exist', 101);
		}

		// Get the subadmin groups
		$groups = $this->groupManager->getSubAdmin()->getSubAdminsGroups($user);
		foreach ($groups as $key => $group) {
			$groups[$key] = $group->getGID();
		}

		if(!$groups) {
			throw new OCSException('Unknown error occurred', 102);
		} else {
			return new DataResponse($groups);
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
