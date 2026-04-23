<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Provisioning_API\Controller;

use OCA\Provisioning_API\ResponseDefinitions;
use OCA\Settings\Settings\Admin\Sharing;
use OCA\Settings\Settings\Admin\Users;
use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\Files\IRootFolder;
use OCP\Group\ISubAdmin;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type Provisioning_APIGroupDetails from ResponseDefinitions
 * @psalm-import-type Provisioning_APIUserDetails from ResponseDefinitions
 */
class GroupsController extends AUserDataOCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		IUserManager $userManager,
		IConfig $config,
		IGroupManager $groupManager,
		IUserSession $userSession,
		IAccountManager $accountManager,
		ISubAdmin $subAdminManager,
		IFactory $l10nFactory,
		IRootFolder $rootFolder,
		private LoggerInterface $logger,
	) {
		parent::__construct($appName,
			$request,
			$userManager,
			$config,
			$groupManager,
			$userSession,
			$accountManager,
			$subAdminManager,
			$l10nFactory,
			$rootFolder,
		);
	}

	/**
	 * Get a list of groups
	 *
	 * @param string $search Text to search for
	 * @param ?int $limit Limit the amount of groups returned
	 * @param int $offset Offset for searching for groups
	 * @return DataResponse<Http::STATUS_OK, array{groups: list<string>}, array{}>
	 *
	 * 200: Groups returned
	 */
	#[NoAdminRequired]
	public function getGroups(string $search = '', ?int $limit = null, int $offset = 0): DataResponse {
		$groups = $this->groupManager->search($search, $limit, $offset);
		$groups = array_map(function ($group) {
			/** @var IGroup $group */
			return $group->getGID();
		}, $groups);

		return new DataResponse(['groups' => $groups]);
	}

	/**
	 * Get a list of groups details
	 *
	 * @param string $search Text to search for
	 * @param ?int $limit Limit the amount of groups returned
	 * @param int $offset Offset for searching for groups
	 * @return DataResponse<Http::STATUS_OK, array{groups: list<Provisioning_APIGroupDetails>}, array{}>
	 *
	 * 200: Groups details returned
	 */
	#[NoAdminRequired]
	#[AuthorizedAdminSetting(settings: Sharing::class)]
	#[AuthorizedAdminSetting(settings: Users::class)]
	public function getGroupsDetails(string $search = '', ?int $limit = null, int $offset = 0): DataResponse {
		$groups = $this->groupManager->search($search, $limit, $offset);
		$groups = array_map(function ($group) {
			/** @var IGroup $group */
			return [
				'id' => $group->getGID(),
				'displayname' => $group->getDisplayName(),
				'usercount' => $group->count(),
				'disabled' => $group->countDisabled(),
				'canAdd' => $group->canAddUser(),
				'canRemove' => $group->canRemoveUser(),
			];
		}, $groups);

		return new DataResponse(['groups' => $groups]);
	}

	/**
	 * Get a list of users in the specified group
	 *
	 * @param string $groupId ID of the group
	 * @return DataResponse<Http::STATUS_OK, array{users: list<string>}, array{}>
	 * @throws OCSException
	 *
	 * @deprecated 14 Use getGroupUsers
	 *
	 * 200: Group users returned
	 */
	#[NoAdminRequired]
	public function getGroup(string $groupId): DataResponse {
		return $this->getGroupUsers($groupId);
	}

	/**
	 * @NoSubAdminRequired
	 *
	 * Get a list of users in the specified group
	 *
	 * @param string $groupId ID of the group
	 * @return DataResponse<Http::STATUS_OK, array{users: list<string>}, array{}>
	 * @throws OCSException
	 * @throws OCSNotFoundException Group not found
	 * @throws OCSForbiddenException Missing permissions to get users in the group
	 *
	 * 200: User IDs returned
	 */
	#[NoAdminRequired]
	public function getGroupUsers(string $groupId): DataResponse {
		$groupId = urldecode($groupId);

		$user = $this->userSession->getUser();
		$isSubadminOfGroup = false;

		// Check the group exists
		$group = $this->groupManager->get($groupId);
		if ($group !== null) {
			$isSubadminOfGroup = $this->groupManager->getSubAdmin()->isSubAdminOfGroup($user, $group);
			$isMember = $this->groupManager->isInGroup($user->getUID(), $group->getGID());
		} else {
			throw new OCSNotFoundException('The requested group could not be found');
		}

		// Check subadmin has access to this group
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$isDelegatedAdmin = $this->groupManager->isDelegatedAdmin($user->getUID());
		if ($isAdmin || $isDelegatedAdmin || $isSubadminOfGroup || $isMember) {
			// Honor nested-group edges: return the union of direct members
			// and every descendant group's direct members.
			$users = [];
			foreach ($this->groupManager->getGroupEffectiveDescendantIds($group) as $gid) {
				$descendant = $this->groupManager->get($gid);
				if ($descendant === null) {
					continue;
				}
				foreach ($descendant->getUsers() as $member) {
					$users[$member->getUID()] = true;
				}
			}
			/** @var list<string> $users */
			$users = array_values(array_keys($users));
			return new DataResponse(['users' => $users]);
		}

		throw new OCSForbiddenException();
	}

	/**
	 * Get a list of users details in the specified group
	 *
	 * @param string $groupId ID of the group
	 * @param string $search Text to search for
	 * @param int|null $limit Limit the amount of groups returned
	 * @param int $offset Offset for searching for groups
	 *
	 * @return DataResponse<Http::STATUS_OK, array{users: array<string, Provisioning_APIUserDetails|array{id: string}>}, array{}>
	 * @throws OCSException
	 *
	 * 200: Group users details returned
	 */
	#[NoAdminRequired]
	public function getGroupUsersDetails(string $groupId, string $search = '', ?int $limit = null, int $offset = 0): DataResponse {
		$groupId = urldecode($groupId);
		$currentUser = $this->userSession->getUser();

		// Check the group exists
		$group = $this->groupManager->get($groupId);
		if ($group !== null) {
			$isSubadminOfGroup = $this->groupManager->getSubAdmin()->isSubAdminOfGroup($currentUser, $group);
		} else {
			throw new OCSException('The requested group could not be found', OCSController::RESPOND_NOT_FOUND);
		}

		// Check subadmin has access to this group
		$isAdmin = $this->groupManager->isAdmin($currentUser->getUID());
		$isDelegatedAdmin = $this->groupManager->isDelegatedAdmin($currentUser->getUID());
		if ($isAdmin || $isDelegatedAdmin || $isSubadminOfGroup) {
			// Honor nested-group edges: the effective user set of a parent
			// group is the union of its own direct members and the members
			// of every descendant group.
			$users = [];
			$seen = [];
			foreach ($this->groupManager->getGroupEffectiveDescendantIds($group) as $gid) {
				$descendant = $this->groupManager->get($gid);
				if ($descendant === null) {
					continue;
				}
				foreach ($descendant->searchUsers($search) as $user) {
					$uid = $user->getUID();
					if (isset($seen[$uid])) {
						continue;
					}
					$seen[$uid] = true;
					$users[] = $user;
				}
			}
			if ($offset > 0) {
				$users = array_slice($users, $offset);
			}
			if ($limit !== null && $limit >= 0) {
				$users = array_slice($users, 0, $limit);
			}

			// Extract required number
			$usersDetails = [];
			foreach ($users as $user) {
				try {
					/** @var IUser $user */
					$userId = (string)$user->getUID();
					$userData = $this->getUserData($userId);
					// Do not insert empty entry
					if ($userData !== null) {
						$usersDetails[$userId] = $userData;
					} else {
						// Logged user does not have permissions to see this user
						// only showing its id
						$usersDetails[$userId] = ['id' => $userId];
					}
				} catch (OCSNotFoundException $e) {
					// continue if a users ceased to exist.
				}
			}
			return new DataResponse(['users' => $usersDetails]);
		}

		throw new OCSException('The requested group could not be found', OCSController::RESPOND_NOT_FOUND);
	}

	/**
	 * Create a new group
	 *
	 * @param string $groupid ID of the group
	 * @param string $displayname Display name of the group
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Group created successfully
	 */
	#[AuthorizedAdminSetting(settings:Users::class)]
	#[PasswordConfirmationRequired]
	public function addGroup(string $groupid, string $displayname = ''): DataResponse {
		// Validate name
		if (empty($groupid)) {
			$this->logger->error('Group name not supplied', ['app' => 'provisioning_api']);
			throw new OCSException('Invalid group name', 101);
		}
		// Check if it exists
		if ($this->groupManager->groupExists($groupid)) {
			throw new OCSException('group exists', 102);
		}
		$group = $this->groupManager->createGroup($groupid);
		if ($group === null) {
			throw new OCSException('Not supported by backend', 103);
		}
		if ($displayname !== '') {
			$group->setDisplayName($displayname);
		}
		return new DataResponse();
	}

	/**
	 * Update a group
	 *
	 * @param string $groupId ID of the group
	 * @param string $key Key to update, only 'displayname'
	 * @param string $value New value for the key
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Group updated successfully
	 */
	#[AuthorizedAdminSetting(settings:Users::class)]
	#[PasswordConfirmationRequired]
	public function updateGroup(string $groupId, string $key, string $value): DataResponse {
		$groupId = urldecode($groupId);

		if ($key === 'displayname') {
			$group = $this->groupManager->get($groupId);
			if ($group === null) {
				throw new OCSException('Group does not exist', OCSController::RESPOND_NOT_FOUND);
			}
			if ($group->setDisplayName($value)) {
				return new DataResponse();
			}

			throw new OCSException('Not supported by backend', 101);
		} else {
			throw new OCSException('', OCSController::RESPOND_UNKNOWN_ERROR);
		}
	}

	/**
	 * Delete a group
	 *
	 * @param string $groupId ID of the group
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Group deleted successfully
	 */
	#[AuthorizedAdminSetting(settings:Users::class)]
	#[PasswordConfirmationRequired]
	public function deleteGroup(string $groupId): DataResponse {
		$groupId = urldecode($groupId);

		// Check it exists
		if (!$this->groupManager->groupExists($groupId)) {
			throw new OCSException('', 101);
		} elseif ($groupId === 'admin' || !$this->groupManager->get($groupId)->delete()) {
			// Cannot delete admin group
			throw new OCSException('', 102);
		}

		return new DataResponse();
	}

	/**
	 * Get the list of user IDs that are a subadmin of the group
	 *
	 * @param string $groupId ID of the group
	 * @return DataResponse<Http::STATUS_OK, list<string>, array{}>
	 * @throws OCSException
	 *
	 * 200: Sub admins returned
	 */
	#[AuthorizedAdminSetting(settings:Users::class)]
	public function getSubAdminsOfGroup(string $groupId): DataResponse {
		// Check group exists
		$targetGroup = $this->groupManager->get($groupId);
		if ($targetGroup === null) {
			throw new OCSException('Group does not exist', 101);
		}

		/** @var IUser[] $subadmins */
		$subadmins = $this->groupManager->getSubAdmin()->getGroupsSubAdmins($targetGroup);
		// New class returns IUser[] so convert back
		/** @var list<string> $uids */
		$uids = [];
		foreach ($subadmins as $user) {
			$uids[] = $user->getUID();
		}

		return new DataResponse($uids);
	}

	/**
	 * Get the direct subgroups of a group (one level deep).
	 *
	 * Only edges stored in the nested-group table are returned; transitive
	 * descendants are not. Use effective-member endpoints if you need the
	 * full set of users reachable via nesting.
	 *
	 * @param string $groupId ID of the parent group
	 * @return DataResponse<Http::STATUS_OK, list<string>, array{}>
	 * @throws OCSException
	 *
	 * 200: Direct subgroups returned
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	public function getSubGroups(string $groupId): DataResponse {
		$groupId = urldecode($groupId);
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		$direct = $this->groupManager->getDirectChildGroupIds($groupId);
		return new DataResponse($direct);
	}

	/**
	 * Add a subgroup to a group
	 *
	 * @param string $groupId ID of the parent group
	 * @param string $subGroupId ID of the group to add as a subgroup
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Subgroup added
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	#[PasswordConfirmationRequired]
	public function addSubGroup(string $groupId, string $subGroupId): DataResponse {
		$groupId = urldecode($groupId);
		$parent = $this->groupManager->get($groupId);
		if ($parent === null) {
			throw new OCSException('Parent group does not exist', 101);
		}
		$child = $this->groupManager->get($subGroupId);
		if ($child === null) {
			throw new OCSException('Subgroup does not exist', 102);
		}
		try {
			$this->groupManager->addSubGroup($parent, $child);
		} catch (\OCP\Group\Exception\CycleDetectedException $e) {
			throw new OCSException($e->getMessage(), 103);
		} catch (\OCP\Group\Exception\NestedGroupsNotSupportedException $e) {
			throw new OCSException('Nested groups are not supported by this backend', 104);
		}
		return new DataResponse();
	}

	/**
	 * Remove a subgroup from a group
	 *
	 * @param string $groupId ID of the parent group
	 * @param string $subGroupId ID of the subgroup to remove
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Subgroup removed
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	#[PasswordConfirmationRequired]
	public function removeSubGroup(string $groupId, string $subGroupId): DataResponse {
		$groupId = urldecode($groupId);
		$subGroupId = urldecode($subGroupId);
		$parent = $this->groupManager->get($groupId);
		if ($parent === null) {
			throw new OCSException('Parent group does not exist', 101);
		}
		$child = $this->groupManager->get($subGroupId);
		if ($child === null) {
			throw new OCSException('Subgroup does not exist', 102);
		}
		try {
			$this->groupManager->removeSubGroup($parent, $child);
		} catch (\OCP\Group\Exception\NestedGroupsNotSupportedException $e) {
			throw new OCSException('Nested groups are not supported by this backend', 104);
		}
		return new DataResponse();
	}

	/**
	 * Get the groups designated as sub-admins of a group
	 *
	 * @param string $groupId ID of the group
	 * @return DataResponse<Http::STATUS_OK, list<string>, array{}>
	 * @throws OCSException
	 *
	 * 200: Admin groups returned
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	public function getGroupSubAdmins(string $groupId): DataResponse {
		$groupId = urldecode($groupId);
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		$adminGroups = $this->groupManager->getSubAdmin()->getGroupSubAdminsOfGroup($group);
		/** @var list<string> $gids */
		$gids = array_map(static fn (IGroup $g): string => $g->getGID(), $adminGroups);
		return new DataResponse($gids);
	}

	/**
	 * Designate a group as sub-admin of another group
	 *
	 * @param string $groupId ID of the group to be administered
	 * @param string $adminGroupId ID of the group to grant sub-admin rights
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Group sub-admin created
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	#[PasswordConfirmationRequired]
	public function addGroupSubAdmin(string $groupId, string $adminGroupId): DataResponse {
		$groupId = urldecode($groupId);
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		$adminGroup = $this->groupManager->get($adminGroupId);
		if ($adminGroup === null) {
			throw new OCSException('Admin group does not exist', 102);
		}
		$this->groupManager->getSubAdmin()->createGroupSubAdmin($adminGroup, $group);
		return new DataResponse();
	}

	/**
	 * Revoke sub-admin rights for a group
	 *
	 * @param string $groupId ID of the group
	 * @param string $adminGroupId ID of the admin group
	 * @return DataResponse<Http::STATUS_OK, list<empty>, array{}>
	 * @throws OCSException
	 *
	 * 200: Group sub-admin removed
	 */
	#[AuthorizedAdminSetting(settings: Users::class)]
	#[PasswordConfirmationRequired]
	public function removeGroupSubAdmin(string $groupId, string $adminGroupId): DataResponse {
		$groupId = urldecode($groupId);
		$adminGroupId = urldecode($adminGroupId);
		$group = $this->groupManager->get($groupId);
		if ($group === null) {
			throw new OCSException('Group does not exist', 101);
		}
		$adminGroup = $this->groupManager->get($adminGroupId);
		if ($adminGroup === null) {
			throw new OCSException('Admin group does not exist', 102);
		}
		$this->groupManager->getSubAdmin()->deleteGroupSubAdmin($adminGroup, $group);
		return new DataResponse();
	}
}
