<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
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
use OC\Settings\Mailer\NewUserMailHelper;
use OC_Helper;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCSController;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class UsersController extends OCSController {

	/** @var IUserManager */
	private $userManager;
	/** @var IConfig */
	private $config;
	/** @var IAppManager */
	private $appManager;
	/** @var IGroupManager|\OC\Group\Manager */ // FIXME Requires a method that is not on the interface
	private $groupManager;
	/** @var IUserSession */
	private $userSession;
	/** @var AccountManager */
	private $accountManager;
	/** @var ILogger */
	private $logger;
	/** @var IFactory */
	private $l10nFactory;
	/** @var NewUserMailHelper */
	private $newUserMailHelper;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IUserManager $userManager
	 * @param IConfig $config
	 * @param IAppManager $appManager
	 * @param IGroupManager $groupManager
	 * @param IUserSession $userSession
	 * @param AccountManager $accountManager
	 * @param ILogger $logger
	 * @param IFactory $l10nFactory
	 * @param NewUserMailHelper $newUserMailHelper
	 */
	public function __construct($appName,
								IRequest $request,
								IUserManager $userManager,
								IConfig $config,
								IAppManager $appManager,
								IGroupManager $groupManager,
								IUserSession $userSession,
								AccountManager $accountManager,
								ILogger $logger,
								IFactory $l10nFactory,
								NewUserMailHelper $newUserMailHelper) {
		parent::__construct($appName, $request);

		$this->userManager = $userManager;
		$this->config = $config;
		$this->appManager = $appManager;
		$this->groupManager = $groupManager;
		$this->userSession = $userSession;
		$this->accountManager = $accountManager;
		$this->logger = $logger;
		$this->l10nFactory = $l10nFactory;
		$this->newUserMailHelper = $newUserMailHelper;
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
			$data['enabled'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'enabled', 'true');
		} else {
			// Check they are looking up themselves
			if($currentLoggedInUser->getUID() !== $targetUserObject->getUID()) {
				throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
			}
		}

		$userAccount = $this->accountManager->getUser($targetUserObject);
		$groups = $this->groupManager->getUserGroups($targetUserObject);
		$gids = [];
		foreach ($groups as $group) {
			$gids[] = $group->getDisplayName();
		}

		// Find the data
		$data['id'] = $targetUserObject->getUID();
		$data['quota'] = $this->fillStorageInfo($targetUserObject->getUID());
		$data[AccountManager::PROPERTY_EMAIL] = $targetUserObject->getEMailAddress();
		$data[AccountManager::PROPERTY_DISPLAYNAME] = $targetUserObject->getDisplayName();
		$data[AccountManager::PROPERTY_PHONE] = $userAccount[AccountManager::PROPERTY_PHONE]['value'];
		$data[AccountManager::PROPERTY_ADDRESS] = $userAccount[AccountManager::PROPERTY_ADDRESS]['value'];
		$data[AccountManager::PROPERTY_WEBSITE] = $userAccount[AccountManager::PROPERTY_WEBSITE]['value'];
		$data[AccountManager::PROPERTY_TWITTER] = $userAccount[AccountManager::PROPERTY_TWITTER]['value'];
		$data['groups'] = $gids;
		$data['language'] = $this->config->getUserValue($targetUserObject->getUID(), 'core', 'lang');

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
		if($targetUser->getUID() === $currentLoggedInUser->getUID()) {
			// Editing self (display, email)
			if ($this->config->getSystemValue('allow_user_to_change_display_name', true) !== false) {
				$permittedFields[] = 'display';
				$permittedFields[] = AccountManager::PROPERTY_DISPLAYNAME;
				$permittedFields[] = AccountManager::PROPERTY_EMAIL;
			}

			$permittedFields[] = 'password';
			if ($this->config->getSystemValue('force_language', false) === false ||
				$this->groupManager->isAdmin($currentLoggedInUser->getUID())) {
				$permittedFields[] = 'language';
			}

			if ($this->appManager->isEnabledForUser('federatedfilesharing')) {
				$federatedFileSharing = new \OCA\FederatedFileSharing\AppInfo\Application();
				$shareProvider = $federatedFileSharing->getFederatedShareProvider();
				if ($shareProvider->isLookupServerUploadEnabled()) {
					$permittedFields[] = AccountManager::PROPERTY_PHONE;
					$permittedFields[] = AccountManager::PROPERTY_ADDRESS;
					$permittedFields[] = AccountManager::PROPERTY_WEBSITE;
					$permittedFields[] = AccountManager::PROPERTY_TWITTER;
				}
			}

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
				$permittedFields[] = AccountManager::PROPERTY_DISPLAYNAME;
				$permittedFields[] = AccountManager::PROPERTY_EMAIL;
				$permittedFields[] = 'password';
				$permittedFields[] = 'language';
				$permittedFields[] = AccountManager::PROPERTY_PHONE;
				$permittedFields[] = AccountManager::PROPERTY_ADDRESS;
				$permittedFields[] = AccountManager::PROPERTY_WEBSITE;
				$permittedFields[] = AccountManager::PROPERTY_TWITTER;
				$permittedFields[] = 'quota';
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
			case AccountManager::PROPERTY_DISPLAYNAME:
				$targetUser->setDisplayName($value);
				break;
			case 'quota':
				$quota = $value;
				if($quota !== 'none' && $quota !== 'default') {
					if (is_numeric($quota)) {
						$quota = (float) $quota;
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
			case 'language':
				$languagesCodes = $this->l10nFactory->findAvailableLanguages();
				if (!in_array($value, $languagesCodes, true) && $value !== 'en') {
					throw new OCSException('Invalid language', 102);
				}
				$this->config->setUserValue($targetUser->getUID(), 'core', 'lang', $value);
				break;
			case AccountManager::PROPERTY_EMAIL:
				if(filter_var($value, FILTER_VALIDATE_EMAIL)) {
					$targetUser->setEMailAddress($value);
				} else {
					throw new OCSException('', 102);
				}
				break;
			case AccountManager::PROPERTY_PHONE:
			case AccountManager::PROPERTY_ADDRESS:
			case AccountManager::PROPERTY_WEBSITE:
			case AccountManager::PROPERTY_TWITTER:
				$userAccount = $this->accountManager->getUser($targetUser);
				if ($userAccount[$key]['value'] !== $value) {
					$userAccount[$key]['value'] = $value;
					$this->accountManager->updateUser($targetUser, $userAccount);
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
				/** @var IGroup[] $getSubAdminsGroups */
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
	 * @NoAdminRequired
	 *
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

		// If they're not an admin, check they are a subadmin of the group in question
		$loggedInUser = $this->userSession->getUser();
		$subAdminManager = $this->groupManager->getSubAdmin();
		if (!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminOfGroup($loggedInUser, $group)) {
			throw new OCSException('', 104);
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

		if($groupid === null || trim($groupid) === '') {
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
		if (!$this->groupManager->isAdmin($loggedInUser->getUID()) && !$subAdminManager->isSubAdminOfGroup($loggedInUser, $group)) {
			throw new OCSException('', 104);
		}

		// Check they aren't removing themselves from 'admin' or their 'subadmin; group
		if ($targetUser->getUID() === $loggedInUser->getUID()) {
			if ($this->groupManager->isAdmin($loggedInUser->getUID())) {
				if ($group->getGID() === 'admin') {
					throw new OCSException('Cannot remove yourself from the admin group', 105);
				}
			} else {
				// Not an admin, so the user must be a subadmin of this group, but that is not allowed.
				throw new OCSException('Cannot remove yourself from this group as you are a SubAdmin', 105);
			}

		} else if (!$this->groupManager->isAdmin($loggedInUser->getUID())) {
			/** @var IGroup[] $subAdminGroups */
			$subAdminGroups = $subAdminManager->getSubAdminsGroups($loggedInUser);
			$subAdminGroups = array_map(function (IGroup $subAdminGroup) {
				return $subAdminGroup->getGID();
			}, $subAdminGroups);
			$userGroups = $this->groupManager->getUserGroupIds($targetUser);
			$userSubAdminGroups = array_intersect($subAdminGroups, $userGroups);

			if (count($userSubAdminGroups) <= 1) {
				// Subadmin must not be able to remove a user from all their subadmin groups.
				throw new OCSException('Cannot remove user from this group as this is the only remaining group you are a SubAdmin of', 105);
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
			throw new OCSException('Group does not exist',  102);
		}
		// Check if trying to make subadmin of admin group
		if($group->getGID() === 'admin') {
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
		if(!$subAdminManager->isSubAdminOfGroup($user, $group)) {
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

	/**
	 * @NoAdminRequired
	 * @PasswordConfirmationRequired
	 *
	 * resend welcome message
	 *
	 * @param string $userId
	 * @return DataResponse
	 * @throws OCSException
	 */
	public function resendWelcomeMessage($userId) {
		$currentLoggedInUser = $this->userSession->getUser();

		$targetUser = $this->userManager->get($userId);
		if($targetUser === null) {
			throw new OCSException('', \OCP\API::RESPOND_NOT_FOUND);
		}

		// Check if admin / subadmin
		$subAdminManager = $this->groupManager->getSubAdmin();
		if(!$subAdminManager->isUserAccessible($currentLoggedInUser, $targetUser)
			&& !$this->groupManager->isAdmin($currentLoggedInUser->getUID())) {
			// No rights
			throw new OCSException('', \OCP\API::RESPOND_UNAUTHORISED);
		}

		$email = $targetUser->getEMailAddress();
		if ($email === '' || $email === null) {
			throw new OCSException('Email address not available', 101);
		}
		$username = $targetUser->getUID();
		$lang = $this->config->getUserValue($username, 'core', 'lang', 'en');
		if (!$this->l10nFactory->languageExists('settings', $lang)) {
			$lang = 'en';
		}

		$l10n = $this->l10nFactory->get('settings', $lang);

		try {
			$this->newUserMailHelper->setL10N($l10n);
			$emailTemplate = $this->newUserMailHelper->generateTemplate($targetUser, false);
			$this->newUserMailHelper->sendMail($targetUser, $emailTemplate);
		} catch(\Exception $e) {
			$this->logger->error("Can't send new user mail to $email: " . $e->getMessage(), array('app' => 'settings'));
			throw new OCSException('Sending email failed', 102);
		}

		return new DataResponse();
	}
}
