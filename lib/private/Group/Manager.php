<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Group;

use OC\Settings\AuthorizedGroupMapper;
use OC\SubAdmin;
use OCA\Settings\Settings\Admin\Users;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Backend\IBatchMethodsBackend;
use OCP\Group\Backend\ICreateNamedGroupBackend;
use OCP\Group\Backend\IGroupDetailsBackend;
use OCP\Group\Events\BeforeGroupCreatedEvent;
use OCP\Group\Events\BeforeGroupDeletedEvent;
use OCP\Group\Events\BeforeUserAddedEvent;
use OCP\Group\Events\BeforeUserRemovedEvent;
use OCP\Group\Events\GroupCreatedEvent;
use OCP\Group\Events\GroupDeletedEvent;
use OCP\GroupInterface;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Security\Ip\IRemoteAddress;
use OCP\Server;

/**
 * @template-implements IEventListener<BeforeGroupDeletedEvent|BeforeGroupCreatedEvent|BeforeUserAddedEvent|BeforeUserRemovedEvent>
 */
class Manager implements IGroupManager, IEventListener {
	/** @var list<GroupInterface> */
	private array $backends = [];
	/** @var array<string, IGroup> */
	private array $cachedGroups = [];
	/** @var array<string, list<string>> */
	private array $cachedUserGroupsLocal = [];
	private ICache $cachedUserGroups;
	private ?SubAdmin $subAdmin = null;
	private DisplayNameCache $displayNameCache;
	private const MAX_GROUP_LENGTH = 255;

	public function __construct(
		private \OC\User\Manager $userManager,
		private IEventDispatcher $dispatcher,
		ICacheFactory $cacheFactory,
		private IRemoteAddress $remoteAddress,
	) {
		$this->displayNameCache = new DisplayNameCache($cacheFactory, $this);
		$this->cachedUserGroups = $cacheFactory->createDistributed('user_groups_membership');
	}

	#[\Override]
	public function isBackendUsed(string $backendClass): bool {
		return array_any($this->backends, fn (GroupInterface $backend): bool => $backend::class === $backendClass);
	}

	#[\Override]
	public function addBackend(GroupInterface $backend): void {
		$this->backends[] = $backend;
		$this->clearCaches();
	}

	#[\Override]
	public function removeBackend(GroupInterface $backend): void {
		$this->clearCaches();
		if (($i = array_search($backend, $this->backends)) !== false) {
			unset($this->backends[$i]);
		}
	}

	#[\Override]
	public function clearBackends(): void {
		$this->backends = [];
		$this->clearCaches();
	}

	#[\Override]
	public function getBackends(): array {
		return $this->backends;
	}

	protected function clearCaches(): void {
		$this->cachedGroups = [];
		$this->cachedUserGroups->clear();
		$this->cachedUserGroupsLocal = [];
	}

	#[\Override]
	public function get(string $gid): ?IGroup {
		if (isset($this->cachedGroups[$gid])) {
			return $this->cachedGroups[$gid];
		}
		return $this->getGroupObject($gid);
	}

	protected function getGroupObject(string $gid, ?string $displayName = null): ?IGroup {
		$backends = [];
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(Backend::GROUP_DETAILS)) {
				$groupData = $backend->getGroupDetails($gid);
				if (is_array($groupData) && !empty($groupData)) {
					// take the display name from the last backend that has a non-null one
					if (is_null($displayName) && isset($groupData['displayName'])) {
						$displayName = $groupData['displayName'];
					}
					$backends[] = $backend;
				}
			} elseif ($backend->groupExists($gid)) {
				$backends[] = $backend;
			}
		}
		if (count($backends) === 0) {
			return null;
		}
		/** @var GroupInterface[] $backends */
		$this->cachedGroups[$gid] = new Group($gid, $backends, $this->dispatcher, $this->userManager, $displayName);
		return $this->cachedGroups[$gid];
	}

	/**
	 * @brief Batch method to create group objects
	 *
	 * @param list<string> $gids List of groupIds for which we want to create a IGroup object
	 * @param array<string, string> $displayNames Array containing already know display name for a groupId
	 * @return array<string, IGroup>
	 */
	public function getGroupsObjects(array $gids, array $displayNames = []): array {
		$backends = [];
		$groups = [];
		foreach ($gids as $gid) {
			$backends[$gid] = [];
			if (!isset($displayNames[$gid])) {
				$displayNames[$gid] = null;
			}
		}
		foreach ($this->backends as $backend) {
			if ($backend instanceof IGroupDetailsBackend || $backend->implementsActions(GroupInterface::GROUP_DETAILS)) {
				/** @var GroupInterface&IGroupDetailsBackend $backend */
				if ($backend instanceof IBatchMethodsBackend) {
					$groupDatas = $backend->getGroupsDetails($gids);
				} else {
					$groupDatas = [];
					foreach ($gids as $gid) {
						$groupDatas[$gid] = $backend->getGroupDetails($gid);
					}
				}
				foreach ($groupDatas as $gid => $groupData) {
					if (!empty($groupData)) {
						// take the display name from the last backend that has a non-null one
						if (isset($groupData['displayName'])) {
							$displayNames[$gid] = $groupData['displayName'];
						}
						$backends[$gid][] = $backend;
					}
				}
			} else {
				if ($backend instanceof IBatchMethodsBackend) {
					$existingGroups = $backend->groupsExists($gids);
				} else {
					$existingGroups = array_filter($gids, fn (string $gid): bool => $backend->groupExists($gid));
				}
				foreach ($existingGroups as $group) {
					$backends[$group][] = $backend;
				}
			}
		}
		foreach ($gids as $gid) {
			if (count($backends[$gid]) === 0) {
				continue;
			}
			$this->cachedGroups[$gid] = new Group($gid, $backends[$gid], $this->dispatcher, $this->userManager, $displayNames[$gid]);
			$groups[$gid] = $this->cachedGroups[$gid];
		}
		return $groups;
	}

	#[\Override]
	public function groupExists(string $gid): bool {
		return $this->get($gid) instanceof IGroup;
	}

	#[\Override]
	public function createGroup(string $gid): ?IGroup {
		if ($gid === '') {
			return null;
		} elseif ($group = $this->get($gid)) {
			return $group;
		} elseif (mb_strlen($gid) > self::MAX_GROUP_LENGTH) {
			throw new \Exception('Group name is limited to ' . self::MAX_GROUP_LENGTH . ' characters');
		} else {
			$this->dispatcher->dispatchTyped(new BeforeGroupCreatedEvent($gid));
			foreach ($this->backends as $backend) {
				if ($backend->implementsActions(Backend::CREATE_GROUP)) {
					if ($backend instanceof ICreateNamedGroupBackend) {
						$groupName = $gid;
						if (($gid = $backend->createGroup($groupName)) !== null) {
							$group = $this->getGroupObject($gid);
							$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
							return $group;
						}
					} elseif ($backend->createGroup($gid)) {
						$group = $this->getGroupObject($gid);
						$this->dispatcher->dispatchTyped(new GroupCreatedEvent($group));
						return $group;
					}
				}
			}
			return null;
		}
	}

	#[\Override]
	public function search(string $search, ?int $limit = null, ?int $offset = 0): array {
		$groups = [];
		foreach ($this->backends as $backend) {
			$groupIds = $backend->getGroups($search, $limit ?? -1, $offset ?? 0);
			$newGroups = $this->getGroupsObjects($groupIds);
			foreach ($newGroups as $groupId => $group) {
				$groups[$groupId] = $group;
			}
			if (!is_null($limit) && $limit <= 0) {
				return array_values($groups);
			}
		}
		return array_values($groups);
	}

	#[\Override]
	public function getUserGroups(?IUser $user = null): array {
		if (!$user instanceof IUser) {
			return [];
		}
		return $this->getUserIdGroups($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return array<string, IGroup>
	 */
	public function getUserIdGroups(string $uid): array {
		$groupIds = $this->getUserIdGroupIds($uid);
		return $this->getGroupsObjects($groupIds);
	}

	/**
	 * Checks if a userId is in the admin group
	 */
	#[\Override]
	public function isAdmin(string $userId): bool {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(Backend::IS_ADMIN) && $backend->isAdmin($userId)) {
				return true;
			}
		}
		return $this->isInGroup($userId, 'admin');
	}

	#[\Override]
	public function isDelegatedAdmin(string $userId): bool {
		if (!$this->remoteAddress->allowsAdminActions()) {
			return false;
		}

		// Check if the user as admin delegation for users listing
		$authorizedGroupMapper = Server::get(AuthorizedGroupMapper::class);
		$user = $this->userManager->get($userId);
		$authorizedClasses = $authorizedGroupMapper->findAllClassesForUser($user);
		return in_array(Users::class, $authorizedClasses, true);
	}

	#[\Override]
	public function isInGroup(string $userId, string $group): bool {
		return in_array($group, $this->getUserIdGroupIds($userId));
	}

	#[\Override]
	public function getUserGroupIds(IUser $user): array {
		return $this->getUserIdGroupIds($user->getUID());
	}

	/**
	 * @param string $uid the user id
	 * @return list<string>
	 */
	private function getUserIdGroupIds(string $uid): array {
		if (isset($this->cachedUserGroupsLocal[$uid])) {
			return $this->cachedUserGroupsLocal[$uid];
		}
		$groups = $this->cachedUserGroups->get($uid);
		if ($groups === null) {
			$groups = [];
			foreach ($this->backends as $backend) {
				if ($groupIds = $backend->getUserGroups($uid)) {
					$groups = array_merge($groups, $groupIds);
				}
			}
			$this->cachedUserGroups->set($uid, $groups, 60 * 2); // 2min
			$this->cachedUserGroupsLocal[$uid] = $groups;
		}

		return $groups;
	}

	#[\Override]
	public function getDisplayName(string $groupId): ?string {
		return $this->displayNameCache->getDisplayName($groupId);
	}

	#[\Override]
	public function displayNamesInGroup(string $gid, string $search = '', int $limit = -1, int $offset = 0): array {
		$group = $this->get($gid);
		if (is_null($group)) {
			return [];
		}

		$search = trim($search);
		$groupUsers = [];

		if (!empty($search)) {
			// only user backends have the capability to do a complex search for users
			$searchOffset = 0;
			$searchLimit = $limit * 100;
			if ($limit === -1) {
				$searchLimit = 500;
			}

			do {
				$filteredUsers = $this->userManager->searchDisplayName($search, $searchLimit, $searchOffset);
				foreach ($filteredUsers as $filteredUser) {
					if ($group->inGroup($filteredUser)) {
						$groupUsers[] = $filteredUser;
					}
				}
				$searchOffset += $searchLimit;
			} while (count($groupUsers) < $searchLimit + $offset && count($filteredUsers) >= $searchLimit);

			if ($limit === -1) {
				$groupUsers = array_slice($groupUsers, $offset);
			} else {
				$groupUsers = array_slice($groupUsers, $offset, $limit);
			}
		} else {
			$groupUsers = $group->searchUsers('', $limit, $offset);
		}

		$matchingUsers = [];
		foreach ($groupUsers as $groupUser) {
			$matchingUsers[$groupUser->getUID()] = $groupUser->getDisplayName();
		}
		return $matchingUsers;
	}

	public function getSubAdmin(): SubAdmin {
		if (!$this->subAdmin) {
			$this->subAdmin = new SubAdmin(
				$this->userManager,
				$this,
				Server::get(IDBConnection::class),
				$this->dispatcher
			);
		}

		return $this->subAdmin;
	}

	#[\Override]
	public function handle(Event $event): void {
		if ($event instanceof BeforeGroupDeletedEvent) {
			unset($this->cachedGroups[$event->getGroup()->getGID()]);
			$this->cachedUserGroups->clear();
			$this->cachedUserGroupsLocal = [];
		}

		if ($event instanceof BeforeUserAddedEvent || $event instanceof BeforeUserRemovedEvent) {
			$this->cachedUserGroups->remove($event->getUser()->getUID());
			unset($this->cachedUserGroupsLocal[$event->getUser()->getUID()]);
		}

		if ($event instanceof GroupDeletedEvent) {
			$group = $event->getGroup();
			$appManager = Server::get(\OCP\App\IAppManager::class);
			$apps = $appManager->getEnabledAppsForGroup($group);
			foreach ($apps as $appId) {
				$restrictions = $appManager->getAppRestriction($appId);
				if (empty($restrictions)) {
					continue;
				}
				$key = array_search($group->getGID(), $restrictions, true);
				unset($restrictions[$key]);
				$restrictions = array_values($restrictions);
				if (empty($restrictions)) {
					$appManager->disableApp($appId);
				} else {
					$appManager->enableAppForGroups($appId, $restrictions);
				}
			}
		}
	}
}
